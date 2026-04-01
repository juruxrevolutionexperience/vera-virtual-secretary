def chunk_text(text, user_id):  # divide el texto en chunks con ID único por usuario para que el LLM solo reciba lo relevante y no desperdicie tokens
    chunk_size = 500   # 500 chars es el equilibrio entre contexto suficiente y no disparar el coste de tokens
    overlap = 50       # 50 chars compartido entre chunks para no perder contexto en los cortes
    chunks = []        # acumula los chunks para devolverlos todos a embeddings.py de una vez
    start = 0          # índice de corte inicial para que el bucle sepa dónde empieza cada chunk
    while start < len(text):  # recorre el texto completo hasta que todos los chunks estén generados
        end = start + chunk_size          # marca el final del chunk actual sumando 500 chars al inicio
        if text[start:end].strip():                                               # evita vectorizar chunks vacíos que romperían la API de OpenAI
            chunks.append((f"{user_id}_chunk_{len(chunks)}", text[start:end])) # guarda el par (ID único de usuario, texto del chunk) para que embeddings.py lo vectorice sin mezclar clientes
        start = end - overlap             # avanza al siguiente chunk repitiendo 50 chars para no perder contexto en el corte  
    return chunks      # devuelve todos los chunks listos para que embeddings.py los vectorice