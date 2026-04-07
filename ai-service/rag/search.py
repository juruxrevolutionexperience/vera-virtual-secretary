from rag.embeddings import get_collection


def find_relevant(user_id: str, query: str, min_score: float = 0.75) -> list[dict]:
    """
    Busca los chunks más relevantes del cerebro del cliente para la pregunta recibida.
    ChromaDB vectoriza la query internamente — sin coste de API.
    """
    collection = get_collection(user_id)

    # Comprobar que la colección no está vacía antes de buscar
    if collection.count() == 0:
        return []

    try:
        results = collection.query(
            query_texts=[query],       # ChromaDB genera el embedding internamente
            n_results=min(3, collection.count()),
            where={"active": True},    # ignora chunks desactivados
        )
    except Exception:
        return []  # índice no disponible todavía — devuelve vacío para no romper el flujo

    documents = results["documents"][0]
    distances = results["distances"][0]

    chunks = []
    for text, distance in zip(documents, distances):
        score = 1 - distance                    # distancia → score: 1=idéntico, 0=sin relación
        if score >= min_score:
            chunks.append({"text": text, "score": round(score, 3)})

    return chunks
