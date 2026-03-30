def should_escalate(message, triggers):     # devuelve True si el mensaje contienen un trigger
    for item in triggers:           # recorre cada trigger del cliente
        if item in message:         # si el trigger está en el mensaje
            return True   # hay que escalar
    return False          # ningún trigger encontrado, no escalar