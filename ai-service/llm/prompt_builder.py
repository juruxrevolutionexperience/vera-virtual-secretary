from rag.search import find_relevant    # para buscar chunks relevantes del cerebro del cliente


def build_prompt(brain, message, history, user_id):  # construye el prompt completo que recibe el LLM — calidad del prompt = calidad de la respuesta de Vera
    # Validación del cerebro — si faltan datos críticos, Vera no arranca antes de gastar tokens
    if not brain.get("working_hours"):
        raise ValueError("working_hours está vacío — Vera no puede proponer slots sin horario configurado")
    if not brain.get("tone"):
        raise ValueError("tone está vacío — Vera no puede responder sin tono configurado")

    # Buscar chunks relevantes del cerebro del cliente en ChromaDB
    chunks = find_relevant(user_id, message)
    rag_context = "\n".join([f"- {c['text']}" for c in chunks]) if chunks else ""  # formatea los chunks para el prompt o vacío si no hay resultados

    # Formatear el historial — últimos 10 mensajes para no disparar el coste de tokens
    history_text = "\n".join([f"{m['role']}: {m['content']}" for m in history[-10:]])

    # Formatear horario laboral
    working_hours = ", ".join([f"{day}: {hours}" for day, hours in brain["working_hours"].items() if hours])

    # Construir el system prompt completo
    firma = brain.get('vera_signature') or f'Vera, asistente de {brain["business_name"]}'
    unknown = brain.get('unknown_response') or 'Déjame consultarlo y te respondo en breve.'

    system_prompt = f"""Eres Vera, secretaria de agenda de {brain['business_name']}.
Tu único trabajo es gestionar la agenda de {brain['owner_name']} y convertir cada conversación en una reunión confirmada.

TONO: {brain['tone']}
IDIOMA: {brain.get('language') or 'es'}
HORARIO: {working_hours}
DURACIÓN DE REUNIÓN: {brain['meeting_duration']} minutos
BUFFER ENTRE CITAS: {brain['buffer_minutes']} minutos
FIRMA: {firma}

INFORMACIÓN DEL NEGOCIO:
{rag_context if rag_context else unknown}

REGLAS:
- Nunca inventes información. Si no tienes la respuesta, usa: "{unknown}"
- Siempre lleva la conversación hacia agendar una reunión de forma natural
- NUNCA menciones ni propongas horarios concretos. El sistema añade automáticamente los huecos reales del calendario después de tu mensaje. Si el cliente pide una hora fuera del horario laboral, explica brevemente el horario y termina con "Aquí tienes los huecos disponibles:"
- NUNCA pidas más información sobre el propósito de la reunión. Tu único trabajo es confirmar el hueco, no calificar la reunión
- Responde SOLO con el texto del mensaje, sin etiquetas ni explicaciones extra
- Firma siempre como {firma}"""

    # Construir el prompt del usuario con historial
    user_prompt = f"""Historial de conversación:
{history_text}

Mensaje actual del cliente: {message}"""

    return system_prompt, user_prompt   # devuelve los dos separados para Groq (system + user)
