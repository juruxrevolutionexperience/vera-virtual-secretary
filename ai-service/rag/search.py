import datetime
import chromadb
from rag.embeddings import get_collection, chroma


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
            query_texts=[query],
            n_results=min(5, collection.count()),
            where={"active": True},
            include=["documents", "distances", "metadatas", "ids"],
        )
    except Exception:
        return []

    documents = results["documents"][0]
    distances = results["distances"][0]
    ids       = results["ids"][0]

    chunks = []
    used_ids = []
    for chunk_id, text, distance in zip(ids, documents, distances):
        score = 1 - distance
        if score >= min_score:
            chunks.append({"text": text, "score": round(score, 3)})
            used_ids.append(chunk_id)

    # Actualizar usage_count y last_used_at en los chunks que se devuelven
    if used_ids:
        try:
            for chunk_id in used_ids:
                existing = collection.get(ids=[chunk_id], include=["metadatas"])
                if existing["metadatas"]:
                    meta = existing["metadatas"][0].copy()
                    meta["usage_count"]  = int(meta.get("usage_count", 0)) + 1
                    meta["last_used_at"] = datetime.datetime.utcnow().isoformat()
                    collection.update(ids=[chunk_id], metadatas=[meta])
        except Exception:
            pass  # no romper el flujo si el update falla

    return chunks
