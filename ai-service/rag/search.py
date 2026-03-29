from rag.embeddings import generate_embedding
import chromadb
def find_relevant(user_id, query, min_score=0.75):  # busca chunks relevantes en ChromaDB para el usuario
    embedding = generate_embedding(query)           # convierte la pregunta en vector
    chroma = chromadb.Client()                      # conecta con ChromaDB
    collection = chroma.get_or_create_collection(   # coge o crea la colección del usuario
        name=f"user_{user_id}"
    )
    results = collection.query(
        query_embeddings=[embedding],  # el vector de la pregunta
        n_results=3                    # máximo 3 chunks
    )
    chunks = []                  # lista donde guardamos los resultados válidos
    for item in results:         # recorre cada resultado de ChromaDB
        if score >= min_score:   # solo los que superen el score mínimo
            chunks.append({        # añade el resultado válido a la lista
                "text": item,      # el texto del chunk
                "score": score     # la puntuación de similitud
            })
    return chunks                 # devuelve los resultados válidos  