"""Diagnóstico RAG: llama a build_prompt (flujo real) y muestra si el contexto
de negocio (precios/servicios/FAQs) entra en el system_prompt.
Uso: python diag_rag.py    |    Borrar tras diagnosticar."""
from rag.embeddings import get_collection
from llm.prompt_builder import build_prompt

USER_ID = "2"
MESSAGE = "Hola, ¿cuánto cuesta Vera y con qué se conecta?"

# ── 1. Retrieval crudo ──────────────────────────────────────────
col = get_collection(USER_ID)
print(f"Colección user_{USER_ID} — total chunks: {col.count()}\n")

# ── 2. Flujo real: build_prompt ─────────────────────────────────
brain = {
    "business_name": "Jurux",
    "owner_name": "Jose",
    "tone": "cercano",
    "working_hours": {"monday": {"enabled": True, "start": "10:00", "end": "18:00"}},
    "meeting_duration": 45,
    "buffer_minutes": 15,
    "unknown_response": "Lo confirmo y te digo.",
    "escalation_triggers": [],
    "vera_signature": "Vera · Asistente de Jurux",
    "timezone": "Europe/Madrid",
}

system_prompt, user_prompt = build_prompt(brain, MESSAGE, [], USER_ID, mode="auto")

# Extraer el bloque "INFORMACIÓN DEL NEGOCIO" del system_prompt
print("─── ¿Qué ve Vera como INFORMACIÓN DEL NEGOCIO? ───")
import re
m = re.search(r"INFORMACIÓN DEL NEGOCIO:\n(.*?)\n\n", system_prompt, re.DOTALL)
print(m.group(1) if m else "(no se encontró el bloque)")
print("──────────────────────────────────────────────────")
