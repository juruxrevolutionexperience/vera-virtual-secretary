from rag.embeddings import generate_embedding  # para convertir la pregunta en vector antes de buscar
import chromadb

chroma = chromadb.PersistentClient(path="./chroma_db") # persiste el cerebro del cliente en disco para que Vera no lo pierda si el servicio se reinicia


def find_relevant(user_id, query, min_score=0.75):  # busca los chunks más relevantes del cerebro del cliente para la pregunta recibida
    embedding = generate_embedding(query)           # convierte la pregunta en vector para buscar por similitud semántica
    collection = chroma.get_or_create_collection(   # aísla los datos de cada cliente en su propia colección
        name=f"user_{user_id}"
    )
    results = collection.query(
        query_embeddings=[embedding],  # el vector de la pregunta
        n_results=3,                   # máximo 3 chunks — más chunks no mejora la respuesta, solo añade coste
        where={"active": True}         # ignora chunks desactivados para no responder con información obsoleta
    )
    documents = results['documents'][0]  # lista de textos devueltos por ChromaDB
    distances = results['distances'][0]  # lista de distancias — menor distancia = mayor similitud
    chunks = []                          # acumula solo los chunks que superan el umbral de calidad
    for text, distance in zip(documents, distances):  # recorre cada resultado emparejando texto y distancia
        score = 1 - distance                          # convierte distancia en score: 1 = idéntico, 0 = sin relación
        if score >= min_score:                        # descarta resultados por debajo del umbral — Vera prefiere no saber que saber mal
            chunks.append({"text": text, "score": score})  # guarda el chunk válido con su score para que prompt_builder lo use
    return chunks  # devuelve los chunks relevantes o lista vacía si ninguno supera el umbral