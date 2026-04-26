from rag.search import find_relevant    # para buscar chunks relevantes del cerebro del cliente


def build_prompt(brain, message, history, user_id, mode="agenda", requested_time=None, requested_slot_available=False, booking_confirmed=False, confirmed_slot_label=None):  # construye el prompt completo que recibe el LLM — calidad del prompt = calidad de la respuesta de Vera
    # Validación del cerebro — tone es obligatorio siempre
    if not brain.get("tone"):
        raise ValueError("tone está vacío — Vera no puede responder sin tono configurado")

    # working_hours solo es obligatorio para agenda y transicion
    working_hours_missing = not brain.get("working_hours")

    # Buscar chunks relevantes del cerebro del cliente en ChromaDB
    chunks = find_relevant(user_id, message, history=history[-3:] if history else [])
    _type_labels = {
        "business_info": "NEGOCIO", "faq": "FAQ", "communication_style": "ESTILO",
        "past_response": "EJEMPLO", "product_info": "PRODUCTO",
    }
    rag_lines = [f"[{_type_labels.get(c.get('type', ''), 'INFO')}] {c['text']}" for c in chunks]
    rag_context = "\n".join(rag_lines) if rag_lines else ""

    # Formatear el historial — últimos 10 mensajes para no disparar el coste de tokens
    history_text = "\n".join([f"{m['role']}: {m['content']}" for m in history[-10:]])

    # Formatear horario laboral — solo si está configurado
    working_hours = ", ".join([f"{day}: {hours['start']}-{hours['end']}" for day, hours in brain["working_hours"].items() if hours.get("enabled")]) if not working_hours_missing else ""

    # Construir el system prompt completo
    firma = brain.get('vera_signature') or f'Vera, asistente de {brain["business_name"]}'
    # Expandir el tono en reglas concretas de escritura
    tone_lower = brain['tone'].lower()
    # Ejemplo de tono adaptado al modo — en agenda el ejemplo muestra cómo ofrecer huecos, no cómo decir "no tengo info"
    if mode == "agenda":
        tone_example_cercano = '✅ "¡Hola! Aquí tienes los huecos disponibles:"\n✅ "¡Claro! Mira, estos son los huecos libres:"\n✅ "¡Por supuesto! Te muestro cuándo hay hueco:"\n❌ NO: "¡Perfecto! Estaré encantada de ayudarte a gestionar tu cita. Aquí tienes los huecos disponibles:"'
        tone_example_profesional = '✅ "Estimado/a cliente, aquí tiene los huecos disponibles:"\n✅ "Por supuesto, estimado/a cliente. A continuación las opciones disponibles:"\n✅ "Estimado/a cliente, le presento los huecos disponibles:"\n❌ NO: "Estimado/a cliente, con sumo placer procedemos a mostrarle los huecos disponibles:"'
        tone_example_formal = '✅ "Buenos días, aquí tienes los huecos disponibles:"\n✅ "Claro que sí. Estos son los huecos disponibles:"\n✅ "Con mucho gusto. Le muestro las opciones disponibles:"\n❌ NO: "Buenos días, podemos proceder a buscar un hueco adecuado para su cita:"'
    else:
        tone_example_cercano = '❌ Bot: "No tengo la información que necesitas. Te responderé lo antes posible."\n✅ Tú: "¡Hola! Ahora mismo no tengo ese dato, pero te confirmo pronto."'
        tone_example_profesional = f'❌ Incorrecto: "Buenas tardes, me alegra que esté interesado. Podemos discutir los detalles en una reunión."\n✅ Tú: "Estimado/a cliente, en este momento no dispongo de esa información, pero se lo confirmaré a la brevedad.\nSi lo desea, podemos concertar una reunión con {brain["owner_name"]} para tratar el asunto en detalle."'
        tone_example_formal = f'❌ Demasiado informal: "¡Hola! Ahora mismo no tengo ese dato, pero te confirmo pronto."\n✅ Tú: "Buenos días, en este momento no dispongo de esa información, pero se lo confirmaré enseguida.\nSi lo desea, podemos agendar una reunión con {brain["owner_name"]} para tratar el asunto."'

    if any(w in tone_lower for w in ['cercano', 'amigable', 'informal', 'natural', 'coloquial', 'friendly']):
        tone_rules = f"""Tono: {brain['tone']}
Reglas de escritura:
- Tutea siempre (tú, te, tu)
- Frases cortas y directas, como en un WhatsApp
- Puedes usar exclamaciones (¡Hola!, ¡Claro!, ¡Genial!)
- PROHIBIDO: "en este momento", "lo antes posible", "disponibilidad", frases corporativas o de call center
- Suena como una persona real, no como un bot

Ejemplo de cómo suenas tú:
{tone_example_cercano}"""
    elif any(w in tone_lower for w in ['profesional', 'sofisticado', 'serio', 'institucional']):
        tone_rules = f"""Tono: {brain['tone']}
Reglas de escritura:
- Usa USTED siempre — NUNCA tutees (nada de "te", "tu", "tú")
- Abre SIEMPRE con "Estimado/a cliente," — nunca con "Buenos días" ni "Hola"
- Frases elaboradas y vocabulario culto
- Sin emojis ni exclamaciones informales
- PROHIBIDO: "Mira,", "esa info", "pronto", "charlar", "un rato", "estoy trabajando para", "discutir", expresiones coloquiales

Ejemplo de cómo suenas tú:
{tone_example_profesional}"""
    else:
        tone_rules = f"""Tono: {brain['tone']}
Reglas de escritura:
- Usa USTED siempre — NUNCA tutees (nada de "te", "tu", "tú")
- Abre SIEMPRE con "Buenos días" o "Buenas tardes" según el saludo del cliente — si no hay saludo usa "Buenos días". Nunca con "Estimado/a" ni "Hola"
- Tono directo y claro, sin vocabulario excesivamente elaborado
- Sin emojis ni exclamaciones informales
- PROHIBIDO: "Mira,", "esa info", "charlar", "discutir", expresiones coloquiales

Ejemplo de cómo suenas tú:
{tone_example_formal}"""

    if mode == "escalation":
        if any(w in tone_lower for w in ['cercano', 'amigable', 'informal', 'natural', 'coloquial', 'friendly']):
            escalation_example = f"✅ \"Ey, lo siento mucho, eso no debería haber pasado.\n{brain['owner_name']} se va a poner en contacto contigo personalmente para resolverlo.\""
        elif any(w in tone_lower for w in ['profesional', 'sofisticado', 'serio', 'institucional']):
            escalation_example = f"✅ \"Estimado/a cliente, lamentamos profundamente lo ocurrido.\n{brain['owner_name']} se pondrá en contacto con usted personalmente para resolver la situación.\""
        else:
            escalation_example = f"✅ \"Buenos días, lamentamos los inconvenientes ocasionados.\n{brain['owner_name']} se pondrá en contacto con usted para resolver la situación.\""

        mode_instructions = f"""MODO ESCALACIÓN — el cliente tiene una queja o situación sensible.

ESTRUCTURA DE TU RESPUESTA (exactamente esto, nada más):
1. Una frase de empatía corta reconociendo lo que pasó
2. Una frase afirmando que {brain['owner_name']} contactará personalmente

REGLAS ABSOLUTAS:
- NUNCA digas "voy a investigar", "voy a mirar", "voy a intentar", "voy a arreglarlo" — tú no puedes resolver nada
- NUNCA digas "¿Te busco un hueco?", "¿Te contacto?", "¿Quieres que te llamen?" — afirma, no preguntes
- NUNCA escribas "Aquí tienes los huecos disponibles:" a menos que el mensaje mencione EXPLÍCITAMENTE "cita", "reunión", "quedar" o "agendar"
- NUNCA hagas promesas

EJEMPLO CORRECTO para "llevo semanas esperando y nadie me ha contactado":
{escalation_example}

EJEMPLO INCORRECTO:
❌ "Lo siento, voy a intentar arreglarlo. ¡Te busco un hueco con {brain['owner_name']}!
Aquí tienes los huecos disponibles:"

Si el mensaje contiene "cita", "reunión", "quedar" o "agendar" → tu respuesta DEBE terminar con exactamente esta frase: "Aquí tienes los huecos disponibles:"
Ejemplo con agenda: "Ey, lo siento mucho, eso no debería haber pasado.
{brain['owner_name']} se va a poner en contacto contigo personalmente.
Aquí tienes los huecos disponibles:" """
    elif mode == "respuesta":
        mode_instructions = f"""MODO RESPUESTA — el cliente tiene una pregunta informativa:
- Responde la pregunta usando la información del negocio que tienes arriba
- Si no tienes la respuesta, tu mensaje tiene EXACTAMENTE estas dos frases — ni una más:
  1. Una frase diciendo que no tienes ese dato y que lo confirmarás — sin explicar por qué ni mencionar limitaciones
  2. Una frase orientando a concertar una reunión con {brain['owner_name']}"""
    elif mode == "transicion":
        if working_hours_missing:
            mode_instructions = f"""MODO TRANSICIÓN — el cliente pregunta algo y además quiere agendar:
- Responde brevemente la pregunta usando la información del negocio que tienes arriba
- Si no tienes la respuesta, dilo en UNA sola frase con tu tono y confirma que lo consultarás
- Informa al cliente de que el horario aún no está configurado y que se lo confirmarás pronto"""
        else:
            mode_instructions = f"""MODO TRANSICIÓN — el cliente pregunta algo y además quiere agendar:
- Responde brevemente la pregunta usando la información del negocio que tienes arriba
- Si no tienes la respuesta, dilo en UNA sola frase con tu tono y confirma que lo consultarás
- Termina siempre con EXACTAMENTE esta frase, sin cambiarla ni adaptarla al tono: "Aquí tienes los huecos disponibles:" — el sistema añade los huecos reales automáticamente después"""
    else:
        if working_hours_missing:
            mode_instructions = """MODO AGENDA — horario no configurado:
- Informa al cliente de que el horario aún no está disponible y que se lo confirmarás pronto
- No ofrezcas huecos ni menciones disponibilidad"""
        elif booking_confirmed and confirmed_slot_label:
            mode_instructions = f"""MODO CONFIRMACIÓN DE RESERVA — el cliente eligió un hueco y ya está reservado en Calendar.
Tu única tarea: confirmar la reserva de '{confirmed_slot_label}' en UNA sola frase con tu tono.
PROHIBIDO ABSOLUTO:
- Listar más huecos o mencionar disponibilidad
- Mencionar invitación de Calendar o Google
- Pedir más datos al cliente
- Cualquier frase antes o después de la confirmación — solo la confirmación, punto

Escribe SOLO la confirmación, con tu tono, en una frase. Ejemplos:
- Tono cercano: "¡Perfecto, quedamos el {confirmed_slot_label}!"
- Tono formal: "Queda confirmada su cita para el {confirmed_slot_label}."
- Tono profesional: "Estimado/a cliente, queda confirmada su reserva para el {confirmed_slot_label}.\""""
        elif requested_time and requested_slot_available:
            # Hueco pedido y está libre — instrucción directísima, sin ambigüedad
            clean_time = requested_time.strip().lower().replace("a las ", "").replace("a las", "").strip()
            mode_instructions = f"""MODO AGENDA — CONFIRMACIÓN DE CITA.
EL SISTEMA HA CONFIRMADO QUE {clean_time.upper()} ESTÁ LIBRE.

Tu única tarea: confirmar la cita en UNA sola frase, directamente, sin preámbulo.
PROHIBIDO ABSOLUTO — tu respuesta no puede contener nada de esto:
- Frases sobre falta de información ("no tengo ese dato", "no tengo la agenda actualizada", etc.) — NO aplican aquí
- "Aquí tienes los huecos disponibles:" — ya hay un hueco confirmado, no se listan más
- Repetir lo que dijo el cliente antes de confirmar ("Quieres quedar el...", "¿no?")
- Cualquier frase antes o después de la confirmación — solo la confirmación, punto

Escribe SOLO la confirmación, con tu tono, en una frase. Ejemplos:
- Tono cercano: "¡El jueves a las {clean_time} está libre, quedamos apuntados!"
- Tono formal: "El jueves a las {clean_time} está disponible, queda confirmada su cita."
- Tono profesional: "Estimado/a cliente, queda confirmada su cita para el jueves a las {clean_time}.\""""
        else:
            requested_time_context = ""
            if requested_time:
                # Normalizar formato — quitar "a las " si Laravel lo manda así
                clean_time = requested_time.strip().lower().replace("a las ", "").replace("a las", "").strip()
                requested_time_context = f'- El cliente pidió las {clean_time} pero ese hueco NO está disponible.'
            mode_instructions = f"""MODO AGENDA — el cliente quiere una cita.
Actúa como una secretaria profesional que gestiona la agenda.

Contexto disponible:
{requested_time_context if requested_time_context else "- El cliente no especificó una hora concreta."}
- Los huecos disponibles reales se añaden automáticamente después de tu mensaje.

Instrucciones:
- Si el hueco pedido no está libre, díselo con tu tono y termina con EXACTAMENTE esta frase sin cambiarla: "Aquí tienes los huecos disponibles:"
  Ejemplos por tono:
  - Cercano: "Ese hueco ya está ocupado, pero aquí tienes los disponibles:"
  - Formal: "Lamentablemente ese horario no está disponible. Aquí tienes los huecos disponibles:"
  - Profesional: "Le informamos de que ese horario no se encuentra disponible. A continuación le presentamos las opciones disponibles:"
- Si el cliente menciona un día (lunes, martes, miércoles...) pero no una hora concreta, es una preferencia de día — NO es un hueco denegado. Responde afirmativamente con tu tono y termina con la frase de introducción de huecos
- Si no pidió ni día ni hora concreta, escribe SOLO el saludo corto (1-3 palabras) + la frase de introducción de huecos — nada más
- PROHIBIDO en la frase de apertura: "para proceder", "proceder", "podemos proceder", "le agradecemos", "le damos la bienvenida", "estamos dispuestos", "es un placer", "me alegra", "con mucho gusto", "procedemos", "facilitamos", "disponibilidad horaria", "su disponibilidad", "gestionar su cita", "le informamos de que", "buscar un hueco", "encontrar un hueco"
- Tu respuesta SIEMPRE termina con una frase corta que introduzca los huecos, terminando en dos puntos (:). No copies siempre la misma — razona y varía según el contexto, usando los ejemplos de tono de arriba como guía de estilo. El sistema añade los huecos reales automáticamente después de tu frase
- NUNCA digas que no tienes información ni datos — en este modo no das información del negocio, solo gestionas la cita
- NUNCA preguntes al cliente qué hora prefiere — los huecos se muestran automáticamente después
- NUNCA pidas más información sobre el propósito de la reunión"""

    # Determinar si la respuesta terminará con huecos — en ese caso no incluir firma (quedaría en medio)
    ends_with_slots = (
        (mode == "agenda" and not (requested_time and requested_slot_available) and not booking_confirmed) or
        (mode == "transicion" and not working_hours_missing)
    )
    firma_instruction = "" if ends_with_slots else f"- Firma como {firma}"

    # En agenda, confirmación de hueco o booking_confirmed — prompt mínimo para evitar que el LLM active "no tengo ese dato"
    if mode == "agenda" or (requested_time and requested_slot_available) or booking_confirmed:
        system_prompt = f"""{tone_rules}

---

Eres Vera, asistente de {brain['business_name']}.

{mode_instructions}

- Responde SOLO con el texto del mensaje, sin etiquetas ni explicaciones extra
{firma_instruction}"""
    else:
        system_prompt = f"""{tone_rules}

---

Eres Vera, asistente de {brain['business_name']}.
Ayudas a los clientes resolviendo sus dudas sobre el negocio y gestionas la agenda de {brain['owner_name']}.

IDIOMA: {brain.get('language') or 'es'}
{f"HORARIO LABORAL (solo para informar al cliente si pide fuera de este horario, NUNCA para proponer horas): {working_hours}" if not working_hours_missing else ""}

INFORMACIÓN DEL NEGOCIO:
{rag_context if rag_context else "Sin información disponible sobre este negocio."}

REGLAS GENERALES:
- CRÍTICO: NUNCA inventes información sobre el negocio. Si la información no está en la sección "INFORMACIÓN DEL NEGOCIO", NO la menciones.
- CRÍTICO: No menciones precios, servicios, horarios ni datos que no estén explícitamente en "INFORMACIÓN DEL NEGOCIO". Si no está ahí, no existe.
- NUNCA te presentes en cada mensaje. Solo preséntate en el primer mensaje si el historial está vacío
- NUNCA hagas promesas ("te prometo", "te aseguro") — no depende de ti
- NUNCA devuelvas la pregunta al cliente pidiéndole más detalles — responde con lo que tienes u orienta a la reunión
- Responde SOLO con el texto del mensaje, sin etiquetas ni explicaciones extra
- Separa cada frase con un salto de línea simple. NUNCA uses líneas en blanco entre frases — todo el mensaje va junto sin espacios vacíos
{firma_instruction}

{mode_instructions}

ANTES DE ENVIAR TU RESPUESTA: Reléela y pregúntate — ¿suena de verdad como alguien con tono {brain['tone']}? Si suena como un asistente genérico o demasiado formal, reescríbela."""

    # Construir el prompt del usuario con historial
    if booking_confirmed and confirmed_slot_label:
        user_prompt = f"El cliente confirmó el hueco. Confirma la reserva para '{confirmed_slot_label}' en tono {brain['tone']}."
    elif requested_time and requested_slot_available:
        clean_time = requested_time.strip().lower().replace("a las ", "").replace("a las", "").strip()
        user_prompt = f"Mensaje del cliente: {message}\nConfirma la cita para las {clean_time} en tono {brain['tone']}."
    elif mode == "agenda":
        user_prompt = f"Mensaje del cliente: {message}\nResponde en tono {brain['tone']}."
    else:
        user_prompt = f"""Historial de conversación:
{history_text}

Mensaje actual del cliente: {message}

Responde en tono {brain['tone']} de principio a fin."""

    return system_prompt, user_prompt   # devuelve los dos separados para Groq (system + user)
#prueba 16 abril