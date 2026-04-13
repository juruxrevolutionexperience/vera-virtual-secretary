from llm.client import call_llm         # para detectar el modo en mensajes ambiguos donde las palabras clave no son suficientes


def detect_mode(message, history):      # detecta si el cliente quiere información, agendar o está en transición entre los dos
    # El LLM siempre decide — las keywords no tienen contexto y generan falsos positivos
    # Coste estimado: <1€/mes por cliente incluso en casos de alto volumen
    history_text = "\n".join([f"{m['role']}: {m['content']}" for m in history[-3:]]) if history else ""
    prompt = f"""Analiza el mensaje de un cliente y clasifícalo en uno de estos modos:
- respuesta: el cliente pregunta sobre el negocio (servicios, precios, horarios, equipo, ubicación...)
- agenda: el cliente quiere reservar, quedar o concretar una cita — aunque lo diga con cortesía ("¿podría quedar...?", "¿tendríais hueco...?", "¿sería posible...")
- transicion: el cliente pregunta algo sobre el negocio Y además quiere agendar — las dos cosas a la vez

Razona paso a paso:
1. ¿El cliente pregunta sobre el negocio? (servicios, precios, equipo, ubicación...) → Si solo esto: respuesta
2. ¿El cliente quiere reservar o concretar una cita, aunque sea con cortesía? → Si solo esto: agenda
3. ¿Las dos cosas a la vez? → transicion
IMPORTANTE: "¿podría quedar...?", "¿tendríais hueco...?", "¿hay disponibilidad...?" son siempre agenda, no respuesta.

Ejemplos:
- "hola, buenos días" → respuesta (solo saludo, sin intención de cita)
- "¿qué servicios ofrecéis?" → respuesta
- "¿cuánto cuesta una consulta?" → respuesta
- "quiero una cita para el martes" → agenda
- "me gustaría pedir una cita" → agenda
- "buenos días, me gustaría concertar una reunión" → agenda
- "hola, quisiera pedir cita" → agenda
- "¿podría quedar el martes a las 11?" → agenda
- "¿tendríais hueco esta semana?" → agenda
- "¿hay disponibilidad el jueves por la tarde?" → agenda
- "¿sería posible quedar mañana?" → agenda
- "¿puedo ir mañana a las 10 para que me expliquéis todo?" → agenda
- "¿lleváis casos de divorcio? si es así me gustaría pedir cita" → transicion
- "me gustaría saber si trabajáis con empresas y también pedir una reunión" → transicion
- "mi médico me dijo que necesito una cita urgente pero antes quiero saber si tratáis X" → transicion

Historial reciente:
{history_text}

Mensaje actual: {message}

Responde solo con una palabra: respuesta, agenda o transicion."""

    mode = call_llm(prompt, system="Eres un clasificador de intenciones. Responde solo con una palabra.").strip().lower()
    return mode if mode in ("respuesta", "agenda", "transicion") else "respuesta"  # fallback seguro si el LLM devuelve algo inesperado
