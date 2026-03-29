def chunk_text(text):  # divide el texto en trozos de ~500 chars
    chunk_size = 500   # tamaño máximo de cada trozo
    overlap = 50       # chars que se repiten entre chunks
    chunks = []        # lista donde guardamos los trozos
    start = 0          # posición donde empieza el trozo actual
    while start < len(text):  # mientras quede texto por dividir
        end = start + chunk_size          # posición donde termina el trozo
        chunks.append(text[start:end])    # añade el trozo a la lista
        start = end - overlap             # avanza pero retrocede el overlap   
    return chunks      # devuelve la lista de chunks