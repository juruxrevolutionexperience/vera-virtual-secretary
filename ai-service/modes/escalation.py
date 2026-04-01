def should_escalate(message, triggers):     # detecta si el mensaje contiene un trigger antes de llamar al LLM — ahorra tokens y evita respuestas incorrectas en situaciones sensibles
    message_lower = message.lower()         # normaliza a minúsculas para no perder triggers por mayúsculas
    for trigger in triggers:                # recorre cada trigger configurado por el cliente
        if trigger.lower() in message_lower:  # compara en minúsculas para detectar "URGENTE", "urgente", "Urgente"
            return True                     # hay que escalar — para el flujo y notifica al humano
    return False                            # ningún trigger encontrado — Vera puede continuar
