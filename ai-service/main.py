import re
import json
import os
import hmac
from fastapi import FastAPI, HTTPException, Header
from pydantic import BaseModel
from typing import Optional
from dotenv import load_dotenv
from modes.escalation import should_escalate, resolve_triggers
from llm.prompt_builder import build_prompt, build_synthesis_prompt
from llm.client import call_llm_with_tools, call_llm
from rag.embeddings import store_chunk, deactivate_chunk, delete_user_collection

load_dotenv()

app = FastAPI(title="Vera ai-service", version="1.0.0")

_SECRET = os.getenv("AI_SERVICE_SECRET", "")

def verify_token(x_service_token: Optional[str] = Header(default=None)):
    # compare_digest evita timing attacks sobre el token
    if not _SECRET or not hmac.compare_digest(x_service_token or "", _SECRET):
        raise HTTPException(status_code=401, detail="No autorizado")


# ── Modelos de entrada/salida — contrato con Laravel ─────────────────────────

class HistoryMessage(BaseModel):
    role: str       # "user" | "assistant"
    content: str

class Brain(BaseModel):
    business_name: str
    owner_name: str
    business_address: Optional[str] = None
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
    prompt_tokens: int = 0
    completion_tokens: int = 0
    model_used: str = "llama-3.3-70b-versatile"   # qué LLM atendió de verdad (groq | gemini fallback | none)
    escalation_reason: Optional[str] = None
    # Intención de cita estructurada — Laravel reserva la hora EXACTA con esto
    intent_action: str = "ninguna"      # confirmar | proponer | rechazar | ninguna
    intent_date: Optional[str] = None   # fecha que pide el cliente (YYYY-MM-DD)
    intent_time: Optional[str] = None   # hora que pide el cliente (HH:MM)


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
    # Combina los triggers CORE (universales, siempre activos) con los extras que
    # configure el dueño en su VeraConfig. Una queja humana es universal — el dueño
    # solo añade palabras específicas de su sector (p.ej. "infección" en clínicas).
    triggers = resolve_triggers(brain.get("escalation_triggers"))
    keyword_escalate = should_escalate(request.message, triggers)

    # 2. Construir prompt unificado
    try:
        system_prompt, user_prompt = build_prompt(
            brain, request.message, history, request.user_id,
            mode="escalation" if keyword_escalate else "auto",
            requested_time=request.requested_time,
            requested_slot_available=request.requested_slot_available,
            booking_confirmed=request.booking_confirmed,
            confirmed_slot_label=request.confirmed_slot_label,
            meeting_state=request.meeting_state,
            shown_slots=request.shown_slots,
            customer_profile=request.customer_profile,
            channel=request.channel,
        )
    except ValueError as e:
        raise HTTPException(status_code=400, detail=str(e))

    # 3. Una sola llamada LLM — devuelve modo + respuesta + tokens + intención de cita
    mode, response_text, token_data, intent, model_used = call_llm_with_tools(user_prompt, system_prompt)
    intent = intent or {"fecha": "", "hora": "", "intencion_cita": "ninguna"}

    # Cortocircuito — mensaje irrelevante detectado por el LLM
    if mode == "irrelevante":
        return ProcessResponse(
            response=None,
            mode="irrelevante",
            escalate=False,
            confidence=1.0,
            suggested_slots=[],
            tokens_used=token_data["total"],
            prompt_tokens=token_data["prompt"],
            completion_tokens=token_data["completion"],
            model_used=model_used,
            escalation_reason=None,
        )

    # Respetar escalado por keyword aunque el LLM clasifique diferente
    if keyword_escalate:
        mode = "escalation"

    # Limpiar dobles saltos de línea
    response_text = re.sub(r'\n{2,}', '\n', response_text.strip())

    # En escalation sin intención de agenda — limpiar huecos colados
    if mode == "escalation":
        agenda_keywords = ["cita", "reunión", "reunion", "quedar", "agendar", "reservar"]
        if not any(kw in request.message.lower() for kw in agenda_keywords):
            response_text = re.sub(r'\nAquí tienes los huecos disponibles:.*', '', response_text, flags=re.DOTALL).strip()

    # Escalar a la Bandeja si lo pilló el keyword check O si el LLM clasificó el
    # mensaje como queja. El LLM caza quejas con palabras fuera de la lista core
    # ("decepcionado", "quiero que me devolváis el dinero", "no sois serios"...),
    # que antes Vera respondía con empatía pero SIN avisar al dueño.
    escalate = keyword_escalate or mode == "escalation"
    if not escalate:
        escalation_reason = None
    elif keyword_escalate:
        escalation_reason = "trigger de escalado detectado en el mensaje"
    else:
        escalation_reason = "Vera detectó una queja del cliente"

    return ProcessResponse(
        response=response_text,
        mode=mode,
        escalate=escalate,
        confidence=0.9,
        suggested_slots=[],
        tokens_used=token_data["total"],
        prompt_tokens=token_data["prompt"],
        completion_tokens=token_data["completion"],
        model_used=model_used,
        escalation_reason=escalation_reason,
        intent_action=intent.get("intencion_cita", "ninguna"),
        intent_date=intent.get("fecha") or None,
        intent_time=intent.get("hora") or None,
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


_DELETABLE_CHUNK_TYPES = {"product_info", "faq", "business_info", "communication_style", "past_response"}

@app.delete("/memory/type/{user_id}/{chunk_type}", dependencies=[__import__('fastapi').Depends(verify_token)])
async def delete_memory_by_type(user_id: str, chunk_type: str):
    """Borra físicamente de ChromaDB todos los chunks de un tipo para un usuario.
    Usado antes de re-sincronizar servicios para garantizar que no quedan huérfanos."""
    if chunk_type not in _DELETABLE_CHUNK_TYPES:
        raise HTTPException(status_code=422, detail=f"chunk_type no permitido: {chunk_type}")
    from rag.embeddings import get_collection
    try:
        col = get_collection(user_id)
        if col.count() > 0:
            col.delete(where={"type": chunk_type})
    except Exception:
        pass
    return {"status": "deleted", "user_id": user_id, "chunk_type": chunk_type}


@app.delete("/memory/user/{user_id}", dependencies=[__import__('fastapi').Depends(verify_token)])
async def delete_user_memory(user_id: str):
    """Elimina toda la memoria vectorial de un usuario (GDPR / baja de cuenta)."""
    delete_user_collection(user_id=user_id)
    return {"status": "deleted", "user_id": user_id}


# ── Endpoint auto-síntesis de insights ───────────────────────────────────────

class SynthesizeRequest(BaseModel):
    user_id: str
    history: list[HistoryMessage]
    existing_insights: Optional[dict] = None

class SynthesizeResponse(BaseModel):
    vera_insights: dict

@app.post("/synthesize", response_model=SynthesizeResponse, dependencies=[__import__('fastapi').Depends(verify_token)])
async def synthesize_insights(request: SynthesizeRequest) -> SynthesizeResponse:
    """Analiza la conversación y devuelve vera_insights actualizados.

    Laravel llama aquí al final de cada mensaje procesado para mantener
    el perfil del cliente actualizado sin intervención del dueño."""
    if not request.history:
        raise HTTPException(status_code=400, detail="history no puede estar vacío")

    history = [m.model_dump() for m in request.history]
    system_prompt, user_prompt = build_synthesis_prompt(history, request.existing_insights)

    raw = call_llm(user_prompt, system_prompt, temperature=0.3)

    # Extraer JSON — el LLM a veces añade texto antes/después
    try:
        insights = json.loads(raw.strip())
    except json.JSONDecodeError:
        match = re.search(r'\{.*\}', raw, re.DOTALL)
        if match:
            try:
                insights = json.loads(match.group())
            except json.JSONDecodeError:
                raise HTTPException(status_code=422, detail=f"JSON inválido del LLM: {raw[:300]}")
        else:
            raise HTTPException(status_code=422, detail=f"El LLM no devolvió JSON: {raw[:300]}")

    # Garantizar campos mínimos
    insights.setdefault("last_interaction_summary", "Conversación procesada")
    insights.setdefault("past_topics", [])

    return SynthesizeResponse(vera_insights=insights)


# ── Health check ──────────────────────────────────────────────────────────────

@app.get("/health")
async def health():
    return {"status": "ok", "service": "vera-ai-service", "version": "1.0.0"}
