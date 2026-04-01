from llm.client import call_llm         # para detectar el modo en mensajes ambiguos donde las palabras clave no son suficientes


def detect_mode(message, history):      # detecta si el cliente quiere información, agendar o está en transición entre los dos
    # Detección rápida por palabras clave — evita llamar al LLM en casos obvios
    message_lower = message.lower()
    agenda_keywords = ["reunión", "reunion", "cita", "agendar", "reservar", "disponibilidad", "martes", "lunes", "miércoles", "jueves", "viernes", "mañana", "semana"]
    for keyword in agenda_keywords:
        if keyword in message_lower:
            return "agenda"             # el cliente quiere agendar directamente

    # Si no hay palabras clave claras, el LLM decide
    history_text = "\n".join([f"{m['role']}: {m['content']}" for m in history[-3:]]) if history else ""
    prompt = f"""Clasifica este mensaje en uno de estos modos:
- respuesta: el cliente hace una pregunta o pide información
- agenda: el cliente quiere agendar una reunión
- transicion: empieza como pregunta pero deriva a querer agendar

Historial reciente:
{history_text}

Mensaje actual: {message}

Responde solo con una palabra: respuesta, agenda o transicion."""

    mode = call_llm(prompt, system="Eres un clasificador de intenciones. Responde solo con una palabra.")
    return mode.strip().lower()         # limpia espacios y devuelve el modo detectado
