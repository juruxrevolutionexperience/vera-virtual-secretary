import re
import os
from fastapi import FastAPI, HTTPException, Header
from pydantic import BaseModel
from typing import Optional
from dotenv import load_dotenv
from modes.escalation import should_escalate
from llm.prompt_builder import build_prompt
from llm.client import call_llm_with_tools
from rag.embeddings import store_chunk, deactivate_chunk, delete_user_collection

load_dotenv()

app = FastAPI(title="Vera ai-service", version="1.0.0")

_SECRET = os.getenv("AI_SERVICE_SECRET", "")

def verify_token(x_service_token: Optional[str] = Header(default=None)):
    if not _SECRET or x_service_token != _SECRET:
        raise HTTPException(status_code=401, detail="No autorizado")


# ── Modelos de entrada/salida — contrato con Laravel ─────────────────────────

class HistoryMessage(BaseModel):
    role: str       # "user" | "assistant"
    content: str

class Brain(BaseModel):
    business_name: str
    owner_name: str
    tone: str
    language: str = "es"
    timezone: str = "Europe/Madrid"
    working_hours: dict
    meeting_duration: int
    buffer_minutes: int
    unknown_response: Optional[str] = "Déjame consultarlo y te respondo en breve."
    escalation_triggers: list[str] = []
    mode: str = "supervised"
    vera_signature: Optional[str] = None
    # Personalidad rica (Fase A) — opcionales, null si no están configurados
    personality_traits: Optional[dict] = None   # {expressions, signature_phrases, taboos, vibe}
    years_at_business: Optional[int] = None
    pov_about_business: Optional[str] = None
    refers_to_owner_as: Optional[str] = None

class ProcessRequest(BaseModel):
    user_id: str
    message: str
    channel: str    # "whatsapp" | "telegram" | "email"
    history: list[HistoryMessage] = []
    brain: Brain
    requested_time: Optional[str] = None        # hora que pidió el cliente, ej. "a las 12:00" — null si no pidió ninguna
    requested_slot_available: bool = False      # True si ese hueco está libre en Google Calendar
    booking_confirmed: bool = False             # True cuando el cliente eligió un hueco y ya está reservado
    confirmed_slot_label: Optional[str] = None  # ej: "jueves 17 de abril a las 09:00"
    meeting_state: Optional[str] = None         # estado actual: new | negotiating | confirmed | escalated
    shown_slots: list[str] = []                 # etiquetas de los slots propuestos en el mensaje anterior
    customer_profile: Optional[dict] = None     # perfil del cliente final si existe en Contact (CP2 - memoria del cliente)

class ProcessResponse(BaseModel):
    response: Optional[str]
    mode: str                   # "respuesta" | "agenda" | "transicion" | "escalation"
    escalate: bool
    confidence: float
    suggested_slots: list[str] = []
    tokens_used: int
    escalation_reason: Optional[str] = None


# ── Modelos para sincronización de memoria (MySQL → ChromaDB) ─────────────────

class MemoryChunk(BaseModel):
    chunk_id: str       # id del registro en MySQL (como string)
    text: str           # contenido completo del chunk
    chunk_type: str     # business_info | faq | communication_style | etc.
    source: str         # onboarding | user_config | manual | auto_detected

class MemorySyncRequest(BaseModel):
    user_id: str
    chunks: list[MemoryChunk]

class MemorySyncResponse(BaseModel):
    synced: int
    user_id: str


# ── Endpoint principal ────────────────────────────────────────────────────────

@app.post("/process", response_model=ProcessResponse, dependencies=[__import__('fastapi').Depends(verify_token)])
async def process_message(request: ProcessRequest) -> ProcessResponse:
    """Endpoint principal. Laravel llama aquí por cada mensaje entrante."""

    brain = request.brain.model_dump()
    history = [m.model_dump() for m in request.history]


    # 1. Escalado — keyword check rápido, sin LLM
    triggers = brain["escalation_triggers"] or [
        # Sustantivos/adjetivos típicos de queja
        "vergüenza", "inaceptable", "queja", "reclamación", "reclamacion",
        "indignado", "indignante", "indignada", "pésimo", "pesimo",
        "harto", "harta", "hartos", "hartas",
        # Tiempo esperando con contexto de queja (evita "llevo dos semanas siguiéndoos")
        "re:llevo\\s+\\S+\\s+(d[ií]as?|semanas?|meses?|horas?)\\s+(esper|sin)",
        "re:hace\\s+\\S+\\s+(d[ií]as?|semanas?|meses?|horas?)[^\\.]{0,40}(esper|sin\\s+respuesta|sin\\s+contestar|sin\\s+llamar)",
        # Ausencia de respuesta del negocio
        "re:nadie\\s+(me|nos)\\s+(contesta|contactan?|ha contactado|han contactado|llama|ha llamado|han llamado)",
        "re:no\\s+(me|nos)\\s+(ha[n]?\\s+)?(contestad|contactad|llamad|respond)",
        "sigo sin respuesta", "siguen sin contestar", "sigo sin noticias",
        # Urgencia / petición explícita de hablar con humano
        "re:exij[oa]", "exigir",
    ]
    escalate = should_escalate(request.message, triggers)

    # 2. Construir prompt unificado
    try:
        system_prompt, user_prompt = build_prompt(
            brain, request.message, history, request.user_id,
            mode="escalation" if escalate else "auto",
            requested_time=request.requested_time,
            requested_slot_available=request.requested_slot_available,
            booking_confirmed=request.booking_confirmed,
            confirmed_slot_label=request.confirmed_slot_label,
            meeting_state=request.meeting_state,
            shown_slots=request.shown_slots,
            customer_profile=request.customer_profile,
        )
    except ValueError as e:
        raise HTTPException(status_code=400, detail=str(e))

    # 3. Una sola llamada LLM — devuelve modo + respuesta juntos
    mode, response_text = call_llm_with_tools(user_prompt, system_prompt)

    # Cortocircuito — mensaje irrelevante detectado por el LLM
    if mode == "irrelevante":
        return ProcessResponse(
            response=None,
            mode="irrelevante",
            escalate=False,
            confidence=1.0,
            suggested_slots=[],
            tokens_used=0,
            escalation_reason=None,
        )

    # Respetar escalado por keyword aunque el LLM clasifique diferente
    if escalate:
        mode = "escalation"

    # Limpiar dobles saltos de línea
    response_text = re.sub(r'\n{2,}', '\n', response_text.strip())

    # En escalation sin intención de agenda — limpiar huecos colados
    if mode == "escalation":
        agenda_keywords = ["cita", "reunión", "reunion", "quedar", "agendar", "reservar"]
        if not any(kw in request.message.lower() for kw in agenda_keywords):
            response_text = re.sub(r'\nAquí tienes los huecos disponibles:.*', '', response_text, flags=re.DOTALL).strip()


    return ProcessResponse(
        response=response_text,
        mode=mode,
        escalate=escalate,
        confidence=0.9,
        suggested_slots=[],
        tokens_used=0,
        escalation_reason="trigger de escalado detectado en el mensaje" if escalate else None,
    )


# ── Endpoints de memoria (MySQL → ChromaDB sync) ──────────────────────────────

@app.post("/memory/sync", response_model=MemorySyncResponse, dependencies=[__import__('fastapi').Depends(verify_token)])
async def sync_memory(request: MemorySyncRequest) -> MemorySyncResponse:
    """
    Laravel llama aquí cada vez que guarda o actualiza chunks en MySQL.
    Guarda los chunks en ChromaDB para búsqueda semántica.
    """
    synced = 0
    for chunk in request.chunks:
        try:
            store_chunk(
                user_id=request.user_id,
                chunk_id=chunk.chunk_id,
                text=chunk.text,
                chunk_type=chunk.chunk_type,
                source=chunk.source,
            )
            synced += 1
        except Exception as e:
            # Log pero no para — un chunk fallido no debe romper todo el sync
            print(f"[memory/sync] Error en chunk {chunk.chunk_id}: {e}")

    return MemorySyncResponse(synced=synced, user_id=request.user_id)


@app.delete("/memory/chunk/{user_id}/{chunk_id}", dependencies=[__import__('fastapi').Depends(verify_token)])
async def deactivate_memory_chunk(user_id: str, chunk_id: str):
    """
    Laravel llama aquí cuando desactiva o borra un chunk en MySQL.
    Lo marca como inactivo en ChromaDB para que no aparezca en búsquedas.
    """
    deactivate_chunk(user_id=user_id, chunk_id=chunk_id)
    return {"status": "deactivated", "chunk_id": chunk_id}


@app.delete("/memory/user/{user_id}", dependencies=[__import__('fastapi').Depends(verify_token)])
async def delete_user_memory(user_id: str):
    """Elimina toda la memoria vectorial de un usuario (GDPR / baja de cuenta)."""
    delete_user_collection(user_id=user_id)
    return {"status": "deleted", "user_id": user_id}


# ── Health check ──────────────────────────────────────────────────────────────

@app.get("/health")
async def health():
    return {"status": "ok", "service": "vera-ai-service", "version": "1.0.0"}
