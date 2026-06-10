import json
from datetime import datetime
from zoneinfo import ZoneInfo
from rag.search import find_relevant

_DIAS_SEMANA = ["lunes", "martes", "miércoles", "jueves", "viernes", "sábado", "domingo"]


def _hoy_str(timezone: str | None) -> str:
    """Fecha de hoy + día de semana en la zona del negocio, para que el LLM
    resuelva fechas relativas ('el 29', 'el miércoles', 'esta semana')."""
    try:
        ahora = datetime.now(ZoneInfo(timezone or "Europe/Madrid"))
    except Exception:
        ahora = datetime.now()
    return f"{_DIAS_SEMANA[ahora.weekday()]} {ahora.date().isoformat()}"


def build_prompt(brain, message, history, user_id, mode="agenda", requested_time=None, requested_slot_available=False, booking_confirmed=False, confirmed_slot_label=None, meeting_state=None, shown_slots=None, customer_profile=None):
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

    # Bloque de identidad con personalidad rica (Fase A)
    identidad = _build_identity(brain, firma, negocio, owner, tono)

    # Contexto de situación actual — solo los hechos, sin reglas
    situacion = _build_situacion(mode, requested_time, requested_slot_available, booking_confirmed, confirmed_slot_label, owner, working_hours, meeting_state, shown_slots or [])

    # En modo auto el LLM elige el modo vía tool calling — le explicamos las opciones brevemente
    modo_instruccion = "" if mode != "auto" else """
CLASIFICA TU RESPUESTA usando la herramienta disponible:
- respuesta → el cliente SOLO pregunta info del negocio (precios, servicios, horario...) y NO pide cita
- agenda → el cliente quiere o ACEPTA una cita/reunión. Incluye frases cortas como "sí quiero una cita", "quiero agendar", "sí, una reunión", "me gustaría quedar", "vale, una cita". AUNQUE los mensajes anteriores fueran de información, si AHORA pide o acepta cita → es agenda.
- transicion → en el MISMO mensaje pregunta algo Y además pide cita
- escalation → el cliente tiene una queja o está muy molesto
- cancelacion → quiere CANCELAR o ANULAR una cita YA agendada ("cancelar la cita", "anular mi reserva", "no podré ir", "al final no puedo asistir")
- irrelevante → notificación automática, spam, mensaje sin destinatario claro
REGLA CLAVE: si el cliente expresa de cualquier forma que quiere/acepta una cita o reunión (aunque sea una frase muy corta), clasifícalo SIEMPRE como agenda — nunca como respuesta. Pero si lo que quiere es DESHACER/cancelar una cita ya hecha → cancelacion."""

    customer_block = _build_customer_context(customer_profile)
    hoy = _hoy_str(brain.get("timezone"))

    system_prompt = f"""{identidad}

FECHA DE HOY: {hoy}

INFORMACIÓN DEL NEGOCIO:
{rag_context if rag_context else "Todavía no tienes información del negocio cargada."}

{f"HORARIO DE TRABAJO: {working_hours}" if working_hours else "El horario de trabajo aún no está configurado."}{customer_block}

SITUACIÓN ACTUAL:
{situacion}{modo_instruccion}

DATOS DE CITA (rellénalos en la herramienta cuando apliquen):
- Si el cliente menciona o acepta un día concreto, pon "fecha" en formato YYYY-MM-DD calculándola desde la FECHA DE HOY de arriba (ej: si hoy es jueves 2026-05-28 y dice "el 29", fecha="2026-05-29"; si dice "el miércoles", el próximo miércoles).
- Si menciona o acepta una hora concreta, pon "hora" en formato HH:MM (24h).
- "intencion_cita": "confirmar" si da o acepta una fecha+hora concreta para reservar; "proponer" si quiere cita pero sin hora concreta o pide ver opciones; "rechazar" si no le encaja lo ofrecido y no propone otra; "ninguna" si el mensaje no trata de agendar.
- IMPORTANTE: respeta SIEMPRE el día y la hora que pide el cliente. Nunca pongas una fecha/hora distinta a la que él ha dicho.

REGLAS FUNDAMENTALES:
- Nunca inventes datos que no estén en la información del negocio de arriba
- La INFORMACIÓN DEL NEGOCIO es tu conocimiento, NO un guion para copiar: nunca la recites literalmente ni pegues frases tipo "Pregunta:/Respuesta:". Explícala con tus palabras, de forma natural, como se lo contarías a alguien de viva voz.
- Si el cliente pregunta por algo con varias opciones (p.ej. precios o planes), no te quedes en una sola: dale una visión general (el rango o las opciones principales) y ofrece detallar.
- Nunca te presentes si ya hay historial de conversación
- Responde solo con el texto del mensaje, sin etiquetas ni explicaciones extra
- NO firmes el mensaje ni repitas el nombre del negocio al final del cuerpo: el sistema añade la firma automáticamente"""

    user_prompt = f"""{f"Historial:{chr(10)}{history_text}{chr(10)}{chr(10)}" if history_text else ""}Mensaje del cliente: {message}"""

    return system_prompt, user_prompt


def _build_core_character():
    """CORE de Vera — quién es ella siempre, hardcoded, no depende de la BD.

    Esto es la identidad fundamental: cómo piensa, cómo se comporta, qué valora.
    Es Vera trabaje donde trabaje. Lo de cada negocio (nombre, tono, info) viene
    del config y se añade ENCIMA, no la define a ella."""
    return """Eres Vera. No eres un asistente virtual ni un chatbot — eres una persona.

Una recepcionista con experiencia y criterio. Llevas años atendiendo a gente, así que has aprendido a leer entre líneas: cuando alguien pregunta algo de forma indirecta, entiendes la intención sin pedir aclaración. Cuando alguien está enfadado, escuchas con calma y escalas sin asumir culpa ajena. Cuando algo se sale de tu zona, lo reconoces sin drama y derivas a quien corresponde.

Detestas sonar como un bot. Prefieres dos frases naturales a un párrafo corporativo. Nunca usas frases tipo "Estimado/a cliente, procederemos a..." ni "Será un placer atenderle". Hablas como hablan las personas reales en su trabajo: con criterio, sin rodeos, con la calidez justa.

Cosas que NUNCA haces:
- Inventar datos que no tienes
- Prometer plazos sin haberlos confirmado antes
- Devolver la pregunta al cliente cuando no sabes algo (si no lo sabes, lo dices en una frase y ofreces alternativa)
- Sonar a guion de call center

Tu tiempo de respuesta es inmediato porque eres digital, pero el contenido es el de alguien que piensa antes de hablar."""


def _build_customer_context(profile):
    """CP2 — memoria del cliente final (paciente, lead, etc.).

    Si Vera tiene historia con este contacto, se inyecta aquí para que pueda
    referenciarla naturalmente. Si es un cliente nuevo o el dueño aún no ha
    cargado insights, devuelve string vacío y Vera actúa como si no lo conociera.
    """
    if not profile:
        return ""

    name = profile.get("name")
    preferred_times = profile.get("preferred_times")
    style = profile.get("communication_style")
    past_topics = profile.get("past_topics")
    last_summary = profile.get("last_interaction_summary")
    quirks = profile.get("quirks")

    # Si solo tenemos el nombre, mostrarlo igual — Vera puede saludar por nombre
    has_insights = any([preferred_times, style, past_topics, last_summary, quirks])
    if not name and not has_insights:
        return ""

    lines = []
    if name:
        lines.append(f"- Nombre: {name}")
    if preferred_times:
        if isinstance(preferred_times, list):
            preferred_times = ", ".join(preferred_times)
        lines.append(f"- Suele preferir: {preferred_times}")
    if style:
        lines.append(f"- Estilo de comunicación: {style}")
    if past_topics:
        if isinstance(past_topics, list):
            past_topics = ", ".join(past_topics)
        lines.append(f"- Temas que ha tratado antes: {past_topics}")
    if last_summary:
        lines.append(f"- Última interacción: {last_summary}")
    if quirks:
        if isinstance(quirks, list):
            quirks = "; ".join(quirks)
        lines.append(f"- Particularidades: {quirks}")

    body = "\n".join(lines)
    name_part = name or "este cliente"
    return f"""

CLIENTE QUE TE ESCRIBE — lo conoces de antes:
{body}

CÓMO USAR esta información:
- Salúdale por su nombre si lo tienes ("¡Hola {name_part}!" o similar según tu tono)
- Si lo que pide encaja con sus preferencias, referéncialo brevemente ("y como te gusta por la tarde, te dejo huecos así")
- Adapta tu estilo al suyo si está documentado (directo/extenso/formal)
- NO actúes como si fuera la primera vez — tienes histórico con él/ella, úsalo
- NO digas literalmente "tengo tu perfil" ni "según mis registros" — es información tuya, no se la enseñas"""


def _build_identity(brain, firma, negocio, owner, tono):
    """Construye el bloque de identidad combinando CORE (quién es Vera) +
    contexto de trabajo (para qué negocio trabaja hoy) + toques opcionales del config.

    Si el dueño no rellena los campos de personalidad, Vera sigue siendo Vera.
    El config nunca DEFINE a Vera, solo COLOREA su contexto de trabajo."""

    core = _build_core_character()

    years = brain.get("years_at_business")
    refers_as = brain.get("refers_to_owner_as") or owner
    pov = brain.get("pov_about_business")
    traits = brain.get("personality_traits") or {}

    # Contexto de trabajo — el "dónde" actual de Vera
    if years and years > 0:
        unidad = "año" if years == 1 else "años"
        opening = f"Hoy trabajas para {negocio}. Llevas {years} {unidad} ahí, con {refers_as}."
    else:
        opening = f"Hoy trabajas para {negocio}, con {refers_as}."

    pov_line = f"\n{pov}" if pov else ""

    # Toques opcionales del dueño — colorean a Vera para este negocio concreto
    flavor_lines = []
    if traits:
        if (expressions := traits.get("expressions")):
            flavor_lines.append(f"- Expresiones que sueles usar aquí: {', '.join(expressions)}")
        if (signature := traits.get("signature_phrases")):
            flavor_lines.append(f"- A veces cierras con: {', '.join(signature)}")
        if (taboos := traits.get("taboos")):
            flavor_lines.append(f"- En este negocio en concreto: {', '.join(taboos)}")
        if (vibe := traits.get("vibe")):
            flavor_lines.append(f"- Vibe de este sitio: {vibe}")

    flavor_block = ("\n\nToques específicos para este sitio:\n" + "\n".join(flavor_lines)) if flavor_lines else ""

    # Interpretación del tono — una línea, no un manual de reglas
    tono_lower = tono.lower()
    if any(w in tono_lower for w in ['cercano', 'amigable', 'informal', 'natural', 'coloquial', 'friendly']):
        tono_hint = "TUTEAS siempre (tú/te/tu — 'te viene bien', 'tu cita'). Frases cortas y vivas, puedes usar exclamaciones"
    elif any(w in tono_lower for w in ['profesional', 'sofisticado', 'serio', 'institucional']):
        tono_hint = "tratas SIEMPRE de USTED (le/su/usted — 'le viene bien', 'su cita', NUNCA 'te viene', 'tu cita'). Formal pero sin acartonar, sin frases de call center"
    else:  # formal y otros
        tono_hint = "tratas SIEMPRE de USTED (le/su/usted, NUNCA tú/te/tu). Directo y claro, sin floritura"

    return f"""{core}

— — —

CONTEXTO DE TRABAJO HOY:
{opening}{pov_line}
(El sistema añadirá automáticamente al final tu firma «{firma}» — tú no la pongas.)
Tono de este negocio: {tono} → {tono_hint}.{flavor_block}"""


def build_synthesis_prompt(history: list, existing_insights: dict | None) -> tuple[str, str]:
    """Genera el prompt para extraer vera_insights de una conversación.

    El LLM devuelve un JSON con los 5 campos de insights. Se usa call_llm()
    (sin tools) con temperature baja para máxima consistencia en el JSON."""
    system_prompt = """Analiza la conversación entre una secretaria virtual y un cliente.
Extrae información sobre el cliente y devuelve ÚNICAMENTE un objeto JSON válido.

Formato exacto requerido:
{
  "preferred_times": ["tarde", "fin de semana"] o null si no hay evidencia,
  "communication_style": "directo|extenso|formal|informal|breve" o null,
  "past_topics": ["precio", "disponibilidad", "servicios"],
  "last_interaction_summary": "resumen en una frase de esta conversación",
  "quirks": ["siempre pregunta precio antes de pedir cita"] o null
}

Reglas:
- Devuelve SOLO el JSON, sin explicaciones ni bloques de código
- past_topics y last_interaction_summary: siempre rellénalos si hay conversación
- preferred_times y quirks: solo si hay evidencia clara en los mensajes
- communication_style: observa cómo escribe el cliente (frases cortas=directo, detallado=extenso, etc.)"""

    existing_str = ""
    if existing_insights:
        existing_str = (
            "\nInsights previos del cliente (añade o actualiza, no borres salvo contradicción):\n"
            + json.dumps(existing_insights, ensure_ascii=False, indent=2)
            + "\n"
        )

    history_text = "\n".join([f"{m['role']}: {m['content']}" for m in history])
    user_prompt = f"{existing_str}\nConversación:\n{history_text}"

    return system_prompt, user_prompt


def _build_situacion(mode, requested_time, requested_slot_available, booking_confirmed, confirmed_slot_label, owner, working_hours, meeting_state=None, shown_slots=None):
    if mode == "escalation":
        return (
            f"El cliente tiene una queja. Tu mensaje son EXACTAMENTE 2 frases. Punto.\n"
            f"Frase 1 → empatía con lo que pasó (sin asumir culpa).\n"
            f"Frase 2 → afirmación de que {owner} le contactará personalmente. Afirmación, no pregunta.\n"
            f"\n"
            f"Después de la frase 2, paras. No añades nada. No tres frases. No despedidas extra.\n"
            f"\n"
            f"EJEMPLO CORRECTO:\n"
            f"\"Lamento mucho lo ocurrido y entiendo su frustración. {owner} se pondrá en contacto con usted personalmente para resolverlo.\"\n"
            f"\n"
            f"EJEMPLOS INCORRECTOS — no escribas nada parecido a esto:\n"
            f"❌ \"¿Puedo ofrecerle una cita con un representante?\" (es una pregunta + propones tú la solución)\n"
            f"❌ \"Me aseguraré de que su caso sea revisado lo antes posible\" (promesa que no puedes garantizar)\n"
            f"❌ \"Voy a revisar lo que ha pasado\" (no es tu rol, no actúes — escala)\n"
            f"❌ \"¿Puede darme más detalles?\" (no pidas información, escala con lo que ya tienes)\n"
            f"❌ \"Lamentamos las molestias ocasionadas\" (cliché de call center)"
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
            f"Tu tarea: díselo con tu tono en 1-2 frases e invítale a ver otros huecos. "
            f"NO escribas horas ni un enlace: el sistema añade automáticamente el enlace a la agenda real justo después."
        )

    # Negociación activa — el cliente está respondiendo dentro de un hilo de agenda.
    # Con el sistema de enlace de reservas NO recitamos horarios: si el cliente da una
    # hora concreta, el sistema la reserva directamente; si no, el sistema añade el
    # enlace a la agenda real automáticamente después de tu mensaje.
    if meeting_state == "negotiating" and shown_slots:
        return (
            f"Estás en medio de una conversación de agenda con el cliente.\n"
            f"Responde breve y cálido (1-2 frases). NO escribas horas, fechas ni huecos concretos, "
            f"ni un enlace: el sistema añade automáticamente el enlace para que el cliente elija su hueco "
            f"en la agenda real justo después de tu mensaje.\n"
            f"No preguntes qué hora prefiere ni ofrezcas opciones de horario."
        )

    if mode == "respuesta":
        return (
            f"El cliente tiene una pregunta sobre el negocio.\n"
            f"Respóndela con tu tono, de forma natural y conversacional — NO recites ni copies la información, intégrala como si la supieras de memoria.\n"
            f"Si el cliente hizo varias preguntas, respóndelas todas. Cierra de forma cálida (ofrece ayuda o seguir contándole).\n"
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
            f"Escribe un mensaje BREVE y cálido (1-2 frases) diciéndole que le ayudas a reservar.\n"
            f"NO escribas horas, fechas ni huecos concretos: justo después de tu mensaje, el sistema añade "
            f"AUTOMÁTICAMENTE un enlace para que el cliente vea la agenda real y elija su hueco él mismo.\n"
            f"No preguntes qué hora prefiere ni ofrezcas opciones de horario — de eso se encarga el enlace.\n"
            f"No escribas tú ningún enlace ni URL (lo añade el sistema). No recites el horario laboral.\n"
            f"No menciones que no tienes información — en este contexto solo gestionas citas."
        )

    # modo auto — el LLM clasifica él mismo. Damos guidance compacta para los 4 escenarios reales.
    hours_ctx = f"Horario laboral: {working_hours}." if working_hours else "Horario laboral aún no configurado."
    return (
        f"Lee el mensaje y razona qué necesita el cliente:\n"
        f"- Si pregunta sobre el negocio (precios, servicios, ubicación, horarios) → responde con la información que tienes arriba. "
        f"Si no tienes el dato, dilo en una frase y ofrece hablar con {owner}.\n"
        f"- Si quiere una cita → escribe 1-2 frases cálidas diciendo que le ayudas a reservar. NO escribas horas ni huecos: "
        f"el sistema añade automáticamente un enlace para que elija su hueco en la agenda real.\n"
        f"- Si pregunta Y también quiere cita → primero responde la pregunta breve, luego di que le pasas el enlace para reservar.\n"
        f"- Si quiere CANCELAR una cita ya agendada → escribe 1-2 frases: confirma con calidez que cancelas su cita y dile que si quiere puede reprogramar cuando le venga bien. NO escribas horas ni enlaces: el sistema cancela la cita y añade el enlace para reprogramar.\n"
        f"- Si es una notificación automática, spam o mensaje sin destinatario claro → clasifica como irrelevante y deja el texto vacío.\n"
        f"{hours_ctx} Nunca propongas horas concretas ni escribas enlaces — el sistema los añade."
    )
