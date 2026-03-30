from rag.search import find_relevant           # para buscar chunks relevantes del RAG
def build_prompt (brain, message, history):    # construye el system prompt con brain, mensaje e historial
    if not brain["working_hours"]:             # si working_hours esta vacío, lanza error.
        raise ValueError("working_hours está vacío")      # para si working_hours no está configurado
    if not brain["tone"]:                      # si tone está vacío, lanza error 
        raise ValueError("tone está vacío")               # para si tone no está configurado
def find_relevant(brain, message, history):        