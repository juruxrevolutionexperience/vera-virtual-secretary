from fastapi import FastAPI, HTTPException
from pydantic import BaseModel
from typing import Optional
from modes.escalation import should_escalate    # se ejecuta antes del LLM para no gastar tokens en situaciones sensibles
from modes.detector import detect_mode          # detecta si el cliente quiere información, agendar o está en transición
from llm.prompt_builder import build_prompt     # construye el prompt completo con brain, RAG e historial
from llm.client import call_llm                 # llama a Groq con fallback a Gemini

app = FastAPI(title="Vera ai-service", version="1.0.0")


# ── Modelos de entrada/salida (contrato con Laravel) ──────────────────────────

class HistoryMessage(BaseModel):
    role: str      # "user" | "assistant"
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
    channel: str   # "whatsapp" | "telegram" | "email"
    history: list[HistoryMessage] = []
    brain: Brain

class ProcessResponse(BaseModel):
    response: Optional[str]
    mode: str                    # "respuesta" | "agenda" | "transicion" | "escalation"
    escalate: bool
    confidence: float
    suggested_slots: list[str] = []
    tokens_used: int
    escalation_reason: Optional[str] = None


# ── Endpoint principal ────────────────────────────────────────────────────────

@app.post("/process", response_model=ProcessResponse)
async def process_message(request: ProcessRequest) -> ProcessResponse:
    """Endpoint principal. Laravel llama aquí por cada mensaje entrante."""

    brain = request.brain.model_dump()  # convierte el objeto Pydantic a diccionario para usarlo en los módulos
    history = [m.model_dump() for m in request.history]

    # 1. Verificar escalado ANTES del LLM — si hay trigger, para y notifica sin gastar tokens
    if should_escalate(request.message, brain["escalation_triggers"]):
        return ProcessResponse(
            response=None,
            mode="escalation",
            escalate=True,
            escalation_reason="trigger de escalado detectado en el mensaje",
            confidence=0.0,
            tokens_used=0,
        )

    # 2. Detectar el modo de conversación
    mode = detect_mode(request.message, history)

    # 3. Construir el prompt con brain + RAG + historial
    try:
        system_prompt, user_prompt = build_prompt(brain, request.message, history, request.user_id)
    except ValueError as e:
        raise HTTPException(status_code=400, detail=str(e))  # brain incompleto — error antes de llamar al LLM

    # 4. Llamar al LLM — Groq con fallback a Gemini
    response_text = call_llm(user_prompt, system_prompt)

    return ProcessResponse(
        response=response_text,
        mode=mode,
        escalate=False,
        confidence=0.9,
        suggested_slots=[],
        tokens_used=0,
    )


@app.get("/health")
async def health():
    return {"status": "ok", "service": "vera-ai-service", "version": "1.0.0"}
