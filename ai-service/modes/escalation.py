import re


# Triggers universales — quejas y urgencias que SIEMPRE deben escalar
# independientemente del sector del negocio. El dueño puede añadir extras
# propios (palabras específicas de su industria) en VeraConfig.escalation_triggers,
# pero estos siempre se aplican.
CORE_TRIGGERS = [
    # Sustantivos/adjetivos típicos de queja
    "vergüenza", "inaceptable", "queja", "reclamación", "reclamacion",
    "indignado", "indignante", "indignada", "pésimo", "pesimo",
    "harto", "harta", "hartos", "hartas",
    # Tiempo esperando con contexto de queja (evita "llevo dos semanas siguiéndoos")
    r"re:llevo\s+\S+\s+(d[ií]as?|semanas?|meses?|horas?)\s+(esper|sin)",
    r"re:hace\s+\S+\s+(d[ií]as?|semanas?|meses?|horas?)[^\.]{0,40}(esper|sin\s+respuesta|sin\s+contestar|sin\s+llamar)",
    # Ausencia de respuesta del negocio
    r"re:nadie\s+(me|nos)\s+(contesta|contactan?|ha contactado|han contactado|llama|ha llamado|han llamado)",
    r"re:no\s+(me|nos)\s+(ha[n]?\s+)?(contestad|contactad|llamad|respond)",
    "sigo sin respuesta", "siguen sin contestar", "sigo sin noticias",
    # Urgencia / exigencia
    r"re:exij[oa]", "exigir",
]


def resolve_triggers(custom_triggers):
    """Combina los triggers core (siempre activos) con los configurados por el dueño.
    Los del dueño son añadidos, no reemplazan a los core — así una queja universal
    nunca pasa desapercibida aunque el dueño no la haya pensado al configurar Vera.
    """
    extras = [t for t in (custom_triggers or []) if t]
    return CORE_TRIGGERS + extras


def should_escalate(message, triggers):
    """Detecta antes del LLM si el mensaje es una queja/urgencia y hay que escalar.
    Los triggers pueden ser substring simple o regex si empiezan con 're:'.
    Cualquier match → True. Recorre triggers en orden y corta en el primero.
    """
    message_lower = message.lower()
    for trigger in triggers:
        t = trigger.lower()
        if t.startswith("re:"):
            if re.search(t[3:], message_lower):
                return True
        elif t in message_lower:
            return True
    return False
