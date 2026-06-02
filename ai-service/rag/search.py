import datetime
import chromadb
from rag.embeddings import get_collection, chroma


_SYNONYMS = {
    # Agenda / citas
    "quedar":      "quedar reunión cita agendar reservar hueco disponibilidad",
    "cita":        "cita reunión agendar reservar hueco quedar",
    "reservar":    "reservar agendar cita reunión hueco",
    "agenda":      "agenda cita reunión hueco disponibilidad reservar",
    "cancelar":    "cancelar anular cita reunión",
    "reprogramar": "reprogramar cambiar mover aplazar cita reunión",
    # Precio
    "precio":      "precio coste tarifa presupuesto cuánto vale plan planes",
    "cuánto":      "cuánto cuesta precio coste tarifa vale presupuesto",
    "cuesta":      "cuesta precio coste tarifa vale presupuesto",
    "tarifa":      "tarifa precio coste plan planes presupuesto",
    "plan":        "plan planes precio tarifa suscripción",
    "pago":        "pago pagar factura cobro método de pago tarjeta",
    # Servicios / producto
    "servicio":    "servicio producto oferta qué hacéis qué ofrecéis catálogo",
    "producto":    "producto servicio artículo catálogo oferta",
    "catálogo":    "catálogo productos servicios oferta",
    # Horario / ubicación
    "horario":     "horario disponibilidad horas cuándo abierto abrís",
    "abierto":     "abierto horario abrís cerráis cuándo",
    "ubicación":   "ubicación dirección dónde sitio local",
    "dónde":       "dónde ubicación dirección sitio local",
    "dirección":   "dirección ubicación dónde sitio",
    # Contacto / info
    "contactar":   "contactar llamar escribir hablar teléfono",
    "teléfono":    "teléfono llamar contacto número móvil",
    "información":  "información detalle explicar cómo funciona",
    # Garantía / postventa
    "garantía":    "garantía devolución reembolso postventa",
    "devolución":  "devolución reembolso garantía cambio",
    # Quejas
    "problema":    "problema incidencia error fallo queja reclamación",
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

    chunks    = []
    used_ids  = []
    near_miss = None   # mejor candidato por debajo del umbral (red de seguridad)
    for chunk_id, text, distance, meta in zip(ids, documents, distances, metadatas):
        score = 1 - distance
        if score >= min_score:
            chunks.append({
                "text":  text,
                "score": round(score, 3),
                "type":  meta.get("type", "info"),
            })
            used_ids.append(chunk_id)
        elif near_miss is None and score >= 0.55:
            # Guardamos el mejor "casi-relevante" (los resultados vienen ordenados,
            # así que el primero por debajo del umbral es el de mayor score).
            near_miss = ({
                "text":  text,
                "score": round(score, 3),
                "type":  meta.get("type", "info"),
            }, chunk_id)

    # Red de seguridad: si NADA supera el umbral pero hay un candidato razonablemente
    # cercano (≥0.55), lo usamos en vez de dejar a Vera sin contexto ("no tengo info").
    # Solo se activa cuando antes se devolvía [] → nunca empeora el comportamiento actual.
    if not chunks and near_miss is not None:
        chunks.append(near_miss[0])
        used_ids.append(near_miss[1])

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
