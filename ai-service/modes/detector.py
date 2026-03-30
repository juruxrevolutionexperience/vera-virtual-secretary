from llm.client import call_llm         # para detectar el modo en casos ambiguos
def detect_mode(message, history):      # devuelve el modo de conversación: respuesta, agenda o transición
    prompt = f"""Clasifica este mensaje en uno de estos modos: respuesta, agenda, transicion
Mensaje: {message}
Responde solo con una palabra: respuesta, agenda o transicion."""  # prompt para el LLM
    mode = call_llm(prompt, system = "")    # llama al LLm para detectar el modo
    return mode.strip().lower()         # limpia espacios y pasa a minúsculas