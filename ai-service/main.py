from fastapi import FastAPI
from pydantic import BaseModel
from typing import Optional

app = FastAPI(title="Vera ai-service", version="0.1.0")


# ── Modelos de entrada/salida (contrato con Laravel) ──────────────────────────

class HistoryMessage(BaseModel):
    role: str      # "user" | "assistant"
    content: str

class Brain(BaseModel):
    business_name: str
    owner_name: str
    tone: str
    working_hours: dict
    meeting_duration: int
    buffer_minutes: int
    unknown_response: Optional[str] = None
    escalation_triggers: list[str] = []
    mode: str  # "supervised" | "autonomous"

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
    """
    Endpoint principal. Laravel llama aquí por cada mensaje entrante.
    TODO: implementar lógica real (RAG + LLM + detector de modos).
    """
    # Placeholder — Roque implementa la lógica real aquí
    return ProcessResponse(
        response=request.brain.unknown_response or "Hola, soy Vera. En breve me pondré en contacto contigo.",
        mode="respuesta",
        escalate=False,
        confidence=0.0,
        suggested_slots=[],
        tokens_used=0,
    )


@app.get("/health")
async def health():
    return {"status": "ok", "service": "vera-ai-service"}
