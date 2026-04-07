from fastapi import FastAPI, HTTPException
from pydantic import BaseModel
from typing import Optional
from modes.escalation import should_escalate
from modes.detector import detect_mode
from llm.prompt_builder import build_prompt
from llm.client import call_llm
from rag.embeddings import store_chunk, deactivate_chunk, delete_user_collection

app = FastAPI(title="Vera ai-service", version="1.0.0")


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

class ProcessRequest(BaseModel):
    user_id: str
    message: str
    channel: str    # "whatsapp" | "telegram" | "email"
    history: list[HistoryMessage] = []
    brain: Brain
    requested_time: Optional[str] = None        # hora que pidió el cliente, ej. "a las 12:00" — null si no pidió ninguna
    requested_slot_available: bool = False      # True si ese hueco está libre en Google Calendar

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

@app.post("/process", response_model=ProcessResponse)
async def process_message(request: ProcessRequest) -> ProcessResponse:
    """Endpoint principal. Laravel llama aquí por cada mensaje entrante."""

    brain = request.brain.model_dump()
    history = [m.model_dump() for m in request.history]

    # 1. Escalado — avisa a Carlos pero sigue generando respuesta para el cliente
    escalate = should_escalate(request.message, brain["escalation_triggers"])

    # 2. Detectar modo de conversación
    mode = "escalation" if escalate else detect_mode(request.message, history)

    # 3. Construir prompt con brain + RAG (ChromaDB) + historial
    try:
        system_prompt, user_prompt = build_prompt(brain, request.message, history, request.user_id, mode, request.requested_time, request.requested_slot_available)
    except ValueError as e:
        raise HTTPException(status_code=400, detail=str(e))

    # 4. LLM — Groq con fallback a Gemini
    response_text = call_llm(user_prompt, system_prompt)

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

@app.post("/memory/sync", response_model=MemorySyncResponse)
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


@app.delete("/memory/chunk/{user_id}/{chunk_id}")
async def deactivate_memory_chunk(user_id: str, chunk_id: str):
    """
    Laravel llama aquí cuando desactiva o borra un chunk en MySQL.
    Lo marca como inactivo en ChromaDB para que no aparezca en búsquedas.
    """
    deactivate_chunk(user_id=user_id, chunk_id=chunk_id)
    return {"status": "deactivated", "chunk_id": chunk_id}


@app.delete("/memory/user/{user_id}")
async def delete_user_memory(user_id: str):
    """Elimina toda la memoria vectorial de un usuario (GDPR / baja de cuenta)."""
    delete_user_collection(user_id=user_id)
    return {"status": "deleted", "user_id": user_id}


# ── Health check ──────────────────────────────────────────────────────────────

@app.get("/health")
async def health():
    return {"status": "ok", "service": "vera-ai-service", "version": "1.0.0"}
