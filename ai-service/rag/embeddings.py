import chromadb
from chromadb.utils import embedding_functions

# ChromaDB genera los vectores localmente con all-MiniLM-L6-v2 (ONNX) — sin coste, sin API externa
chroma = chromadb.PersistentClient(path="./chroma_db")
default_ef = embedding_functions.DefaultEmbeddingFunction()


def get_collection(user_id: str):
    """Devuelve (o crea) la colección del usuario con la función de embedding local."""
    return chroma.get_or_create_collection(
        name=f"user_{user_id}",
        embedding_function=default_ef,
    )


def store_chunk(user_id: str, chunk_id: str, text: str, chunk_type: str, source: str):
    """
    Guarda o actualiza un chunk en ChromaDB.
    ChromaDB vectoriza el texto internamente — no se necesita llamada externa.
    chunk_id es el id del chunk en MySQL, garantiza que no se duplica.
    """
    collection = get_collection(user_id)
    collection.upsert(
        ids=[chunk_id],
        documents=[text],
        metadatas=[{
            "type": chunk_type,
            "active": True,
            "source": source,
        }],
    )


def deactivate_chunk(user_id: str, chunk_id: str):
    """Marca un chunk como inactivo en ChromaDB (no lo borra, para no perder historial)."""
    collection = get_collection(user_id)
    try:
        collection.update(
            ids=[chunk_id],
            metadatas=[{"active": False}],
        )
    except Exception:
        pass  # si no existe el chunk en chroma, no hay nada que desactivar


def delete_user_collection(user_id: str):
    """Elimina toda la memoria vectorial de un usuario (GDPR / baja)."""
    try:
        chroma.delete_collection(f"user_{user_id}")
    except Exception:
        pass
