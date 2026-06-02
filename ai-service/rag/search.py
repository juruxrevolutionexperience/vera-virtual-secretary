import datetime
import chromadb
from rag.embeddings import get_collection, chroma


_SYNONYMS = {
    "quedar":    "quedar reunión cita agendar reservar",
    "precio":    "precio coste tarifa presupuesto cuánto vale",
    "horario":   "horario disponibilidad horas cuándo",
    "servicio":  "servicio producto oferta qué hacéis",
    "contactar": "contactar llamar escribir hablar",
    "información": "información detalle explicar cómo funciona",
    "problema":  "problema incidencia error fallo queja",
}


def _expand_query(query: str) -> str:
    """Añade sinónimos temáticos para mejorar el recall semántico en ChromaDB."""
    expanded = query
    query_lower = query.lower()
    for key, synonyms in _SYNONYMS.items():
        if key in query_lower:
            expanded += " " + synonyms
    return expanded


def find_relevant(user_id: str, query: str, min_score: float = 0.65, history: list = None) -> list[dict]:
    """
    Busca los chunks más relevantes del cerebro del cliente.

    Mejoras aplicadas:
    - Threshold 0.65 (antes 0.75) — captura más contexto relevante sin ruido
    - Query expansion — sinónimos para mejorar recall
    - Historial reciente — enriquece la búsqueda con contexto de la conversación
    - Devuelve el tipo de chunk para que el prompt lo etiquete correctamente
    """
    collection = get_collection(user_id)

    if collection.count() == 0:
        return []

    # Enriquecer query con los últimos 2 mensajes del usuario (contexto conversacional)
    enriched_query = query
    if history:
        recent = [m["content"] for m in history if m.get("role") == "user"][-2:]
        if recent:
            enriched_query = " ".join(recent) + " " + query

    enriched_query = _expand_query(enriched_query)

    try:
        results = collection.query(
            query_texts=[enriched_query],
            n_results=min(10, collection.count()),
            where={"active": True},
            # OJO: NO incluir "ids" — las versiones nuevas de ChromaDB lo rechazan
            # (los ids se devuelven siempre por defecto). Si se incluye, query()
            # lanza excepción → el except devolvía [] y el RAG quedaba mudo.
            include=["documents", "distances", "metadatas"],
        )
    except Exception:
        return []

    documents  = results["documents"][0]
    distances  = results["distances"][0]
    metadatas  = results["metadatas"][0]
    ids        = results["ids"][0]

    chunks   = []
    used_ids = []
    for chunk_id, text, distance, meta in zip(ids, documents, distances, metadatas):
        score = 1 - distance
        if score >= min_score:
            chunks.append({
                "text":  text,
                "score": round(score, 3),
                "type":  meta.get("type", "info"),
            })
            used_ids.append(chunk_id)

    # Actualizar usage_count y last_used_at
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
            pass

    return chunks
