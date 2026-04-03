from llm.client import call_llm         # para detectar el modo en mensajes ambiguos donde las palabras clave no son suficientes


def detect_mode(message, history):      # detecta si el cliente quiere información, agendar o está en transición entre los dos
    # El LLM siempre decide — las keywords no tienen contexto y generan falsos positivos
    # Coste estimado: <1€/mes por cliente incluso en casos de alto volumen
    history_text = "\n".join([f"{m['role']}: {m['content']}" for m in history[-3:]]) if history else ""
    prompt = f"""Analiza el mensaje de un cliente y clasifícalo en uno de estos modos:
- respuesta: el cliente solo hace una pregunta o pide información
- agenda: el cliente solo quiere agendar una cita o reunión
- transicion: el cliente pregunta algo Y además quiere o insinúa querer agendar

Razona paso a paso:
1. ¿El cliente hace una pregunta informativa? (qué hacéis, cuánto cuesta, si lleváis X...)
2. ¿El cliente quiere agendar, reservar o concretar una cita?
3. Si las dos respuestas son sí → transicion. Si solo la primera → respuesta. Si solo la segunda → agenda.

Ejemplos:
- "¿qué servicios ofrecéis?" → respuesta
- "quiero una cita para el martes" → agenda
- "¿lleváis casos de divorcio? si es así me gustaría pedir cita" → transicion
- "mi médico me dijo que necesito una cita urgente pero antes quiero saber si tratáis X" → transicion

Historial reciente:
{history_text}

Mensaje actual: {message}

Responde solo con una palabra: respuesta, agenda o transicion."""

    mode = call_llm(prompt, system="Eres un clasificador de intenciones. Responde solo con una palabra.")
    return mode.strip().lower()         # limpia espacios y devuelve el modo detectado
