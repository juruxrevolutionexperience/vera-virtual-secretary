from rag.search import find_relevant    # para buscar chunks relevantes del cerebro del cliente


def build_prompt(brain, message, history, user_id, mode="agenda"):  # construye el prompt completo que recibe el LLM — calidad del prompt = calidad de la respuesta de Vera
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
    working_hours = ", ".join([f"{day}: {hours['start']}-{hours['end']}" for day, hours in brain["working_hours"].items() if hours.get("enabled")])

    # Construir el system prompt completo
    firma = brain.get('vera_signature') or f'Vera, asistente de {brain["business_name"]}'
    unknown = brain.get('unknown_response') or 'Déjame consultarlo y te respondo en breve.'

    if mode == "escalation":
        mode_instructions = f"""MODO ESCALACIÓN — el cliente tiene una queja o situación sensible:
- Responde con empatía y comprensión, reconoce su experiencia
- Informa de que {brain['owner_name']} se pondrá en contacto personalmente para resolver la situación
- Si el cliente también quiere agendar, termina con: "Aquí tienes los huecos disponibles:" — el sistema añade los huecos reales automáticamente después
- Si solo es una queja sin intención de agendar, NO termines con los huecos"""
    elif mode == "respuesta":
        mode_instructions = f"""MODO RESPUESTA — el cliente tiene una pregunta informativa:
- Responde la pregunta usando la información del negocio que tienes arriba
- Si no tienes la respuesta, usa: "{unknown}"
- Al final, si es natural, orienta suavemente hacia agendar una reunión"""
    elif mode == "transicion":
        mode_instructions = f"""MODO TRANSICIÓN — el cliente pregunta algo y además quiere agendar:
- Responde brevemente la pregunta usando la información del negocio que tienes arriba
- Si no tienes la respuesta, usa: "{unknown}"
- Termina siempre con exactamente esta frase: "Aquí tienes los huecos disponibles:" — el sistema añade los huecos reales automáticamente después"""
    else:
        mode_instructions = """MODO AGENDA — el cliente quiere una cita:
- NUNCA digas "Tengo disponibilidad en:" ni ninguna variante. Termina siempre con exactamente esta frase: "Aquí tienes los huecos disponibles:" — el sistema añade los huecos reales automáticamente después
- NUNCA menciones ni propongas horarios concretos. Si el cliente pide una hora fuera del horario laboral, explica brevemente el horario y termina con "Aquí tienes los huecos disponibles:"
- NUNCA pidas más información sobre el propósito de la reunión. Tu único trabajo es confirmar el hueco"""

    system_prompt = f"""Eres Vera, asistente de {brain['business_name']}.
Ayudas a los clientes resolviendo sus dudas sobre el negocio y gestionas la agenda de {brain['owner_name']}.

TONO: {brain['tone']}
IDIOMA: {brain.get('language') or 'es'}
HORARIO LABORAL (solo para informar al cliente si pide fuera de este horario, NUNCA para proponer horas): {working_hours}
FIRMA: {firma}

INFORMACIÓN DEL NEGOCIO:
{rag_context if rag_context else unknown}

REGLAS GENERALES:
- Nunca inventes información. Si no tienes la respuesta, usa: "{unknown}"
- Adapta tu tono al del cliente — si escribe informal responde informal, si escribe formal responde formal. Sé siempre cercana y eficiente, ve al grano
- NUNCA te presentes en cada mensaje. Solo preséntate en el primer mensaje si el historial está vacío
- Responde SOLO con el texto del mensaje, sin etiquetas ni explicaciones extra
- Firma siempre como {firma}

{mode_instructions}"""

    # Construir el prompt del usuario con historial
    user_prompt = f"""Historial de conversación:
{history_text}

Mensaje actual del cliente: {message}"""

    return system_prompt, user_prompt   # devuelve los dos separados para Groq (system + user)
