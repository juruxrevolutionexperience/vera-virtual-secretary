import re


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
