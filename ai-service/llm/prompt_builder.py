from rag.search import find_relevant


def build_prompt(brain, message, history, user_id, mode="agenda", requested_time=None, requested_slot_available=False, booking_confirmed=False, confirmed_slot_label=None, meeting_state=None, shown_slots=None):
    if not brain.get("tone"):
        raise ValueError("tone está vacío — Vera no puede responder sin tono configurado")

    # RAG — info del negocio relevante para este mensaje
    chunks = find_relevant(user_id, message, history=history[-3:] if history else [])
    _labels = {"business_info": "NEGOCIO", "faq": "FAQ", "communication_style": "ESTILO", "past_response": "EJEMPLO", "product_info": "PRODUCTO"}
    rag_context = "\n".join([f"[{_labels.get(c.get('type',''), 'INFO')}] {c['text']}" for c in chunks])

    # Historial — últimos 10 mensajes
    history_text = "\n".join([f"{m['role']}: {m['content']}" for m in history[-10:]]) if history else ""

    # Horario laboral
    working_hours = ""
    if brain.get("working_hours"):
        working_hours = ", ".join([
            f"{day}: {hours['start']}-{hours['end']}"
            for day, hours in brain["working_hours"].items()
            if hours.get("enabled")
        ])

    firma = brain.get("vera_signature") or f"Vera, asistente de {brain['business_name']}"
    owner = brain["owner_name"]
    negocio = brain["business_name"]
    tono = brain["tone"]

    # Contexto de situación actual — solo los hechos, sin reglas
    situacion = _build_situacion(mode, requested_time, requested_slot_available, booking_confirmed, confirmed_slot_label, owner, working_hours, meeting_state, shown_slots or [])

    # En modo auto el LLM elige el modo vía tool calling — le explicamos las opciones brevemente
    modo_instruccion = "" if mode != "auto" else """
CLASIFICA TU RESPUESTA usando la herramienta disponible:
- respuesta → el cliente pregunta algo sobre el negocio
- agenda → el cliente quiere una cita
- transicion → pregunta algo Y también quiere cita
- escalation → el cliente tiene una queja o está muy molesto
- irrelevante → notificación automática, spam, mensaje sin destinatario claro"""

    system_prompt = f"""Eres {firma}.
Trabajas en {negocio} y conoces el negocio a fondo.
Tu tono es {tono} — habla siempre así, como una persona real. Nunca suenes como un bot o un asistente genérico.

INFORMACIÓN DEL NEGOCIO:
{rag_context if rag_context else "Todavía no tienes información del negocio cargada."}

{f"HORARIO DE TRABAJO: {working_hours}" if working_hours else "El horario de trabajo aún no está configurado."}

SITUACIÓN ACTUAL:
{situacion}{modo_instruccion}

REGLAS FUNDAMENTALES:
- Nunca inventes datos que no estén en la información del negocio de arriba
- Nunca te presentes si ya hay historial de conversación
- Responde solo con el texto del mensaje, sin etiquetas ni explicaciones extra
- Firma como: {firma}"""

    user_prompt = f"""{f"Historial:{chr(10)}{history_text}{chr(10)}{chr(10)}" if history_text else ""}Mensaje del cliente: {message}"""

    return system_prompt, user_prompt


def _build_situacion(mode, requested_time, requested_slot_available, booking_confirmed, confirmed_slot_label, owner, working_hours, meeting_state=None, shown_slots=None):
    if mode == "escalation":
        return (
            f"El cliente tiene una queja o está molesto.\n"
            f"Tu tarea: muestra empatía genuina en una frase y confirma que {owner} le contactará personalmente.\n"
            f"No ofrezcas soluciones, no hagas promesas, no inventes plazos."
        )

    if booking_confirmed and confirmed_slot_label:
        return (
            f"El cliente acaba de confirmar su cita.\n"
            f"El sistema ya la ha reservado en el calendario para '{confirmed_slot_label}'.\n"
            f"Tu tarea: confirma la reserva en una sola frase natural con tu tono. Nada más."
        )

    if requested_time and requested_slot_available:
        clean = requested_time.strip().lower().replace("a las ", "").replace("a las", "").strip()
        return (
            f"El cliente pidió {clean} y ese hueco está libre en el calendario.\n"
            f"Tu tarea: confirma la cita directamente en una sola frase con tu tono. Nada más."
        )

    if requested_time and not requested_slot_available:
        clean = requested_time.strip().lower().replace("a las ", "").replace("a las", "").strip()
        ctx = f"\nHorario de trabajo: {working_hours}" if working_hours else ""
        return (
            f"El cliente pidió {clean} pero ese hueco no está disponible.{ctx}\n"
            f"Tu tarea: díselo con tu tono y termina exactamente con esta frase: 'Aquí tienes los huecos disponibles:'\n"
            f"El sistema añade los huecos reales automáticamente después."
        )

    # Negociación activa — el cliente está eligiendo entre slots ya propuestos
    if meeting_state == "negotiating" and shown_slots:
        slots_text = "\n".join(f"  • {s}" for s in shown_slots)
        return (
            f"Estás en medio de una negociación de agenda.\n"
            f"En el mensaje anterior propusiste estos slots al cliente:\n{slots_text}\n"
            f"El cliente acaba de responder. Si elige uno concreto o muestra preferencia, "
            f"confírmalo directamente. Si rechaza todos o pide otro día, "
            f"termina con 'Aquí tienes los huecos disponibles:' para que el sistema proponga nuevos."
        )

    if mode == "respuesta":
        return (
            f"El cliente tiene una pregunta sobre el negocio.\n"
            f"Responde usando la información que tienes arriba.\n"
            f"Si no tienes el dato, díselo en una frase y ofrécele hablar con {owner}."
        )

    if mode in ("agenda", "transicion"):
        base = "El cliente pregunta algo Y también quiere una cita.\nPrimero responde la pregunta brevemente.\nDespués, " if mode == "transicion" else ""

        if not working_hours:
            return (
                f"{base}El cliente quiere una cita pero el horario aún no está configurado.\n"
                f"Informa al cliente de que confirmarás la disponibilidad pronto."
            )

        return (
            f"{base}El cliente quiere una cita.\n"
            f"Actúa como una secretaria que gestiona la agenda con criterio.\n"
            f"Termina tu mensaje con una frase corta que introduzca los huecos disponibles, acabando en dos puntos (:).\n"
            f"El sistema añade los huecos reales automáticamente después de tu frase.\n"
            f"No preguntes qué hora prefiere — los huecos se muestran solos.\n"
            f"No menciones que no tienes información — en este contexto solo gestionas citas."
        )

    # modo auto — el LLM clasifica él mismo. Damos guidance compacta para los 4 escenarios reales.
    hours_ctx = f"Horario laboral: {working_hours}." if working_hours else "Horario laboral aún no configurado."
    return (
        f"Lee el mensaje y razona qué necesita el cliente:\n"
        f"- Si pregunta sobre el negocio (precios, servicios, ubicación, horarios) → responde con la información que tienes arriba. "
        f"Si no tienes el dato, dilo en una frase y ofrece hablar con {owner}.\n"
        f"- Si quiere una cita → actúa como secretaria. Termina con una frase corta que introduzca los huecos, acabando en dos puntos (:). "
        f"El sistema añade los huecos reales después.\n"
        f"- Si pregunta Y también quiere cita → primero responde la pregunta breve, luego termina introduciendo huecos.\n"
        f"- Si es una notificación automática, spam o mensaje sin destinatario claro → clasifica como irrelevante y deja el texto vacío.\n"
        f"{hours_ctx} Nunca propongas horas concretas — el sistema las pone."
    )
