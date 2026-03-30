import openai # para generar vectores 
import chromadb # para guardar los vectores  
client = openai.OpenAI() #lee OPENAI_API_KEY del .env automáticamente
chroma = chromadb.PersistentClient(path="./chroma_db") # persiste datos en disco entre reinicios

def generate_embedding(text): # genera el vector semántico del texto para que Vera pueda buscar por significado
    response = client.embeddings.create( # convierte el texto en vector usando la API de OpenAI
        model="text-embedding-3-small", # modelo optimizado para búsqueda semántica con bajo coste
        input=text # texto de entrada que se convertirá en vector
    )
    return response.data[0].embedding # vector listo para guardar en ChromaDB o buscar por similitud


def store_embedding(user_id, chunk_id, text, embedding, chunk_type, source):  # almacena el conocimiento del cliente para que Vera lo recupere en cada conversación
    collection = chroma.get_or_create_collection(          # aísla los datos de cada cliente en su propia colección
        name=f"user_{user_id}"
    )
    collection.add(            # guarda el chunk completo en ChromaDB con todo su metadata 
        ids=[chunk_id],        # evita guardar el mismo chunk dos veces
        documents=[text],      # texto que Vera devuelve al LLM cuando este chunk es relevante
        metadatas=[{"type": chunk_type,"active": True, "source": source}], # filtra por categoría, estado activo y origen para mayor precisión, menor uso de tokens y evitar información desactualizada
        embeddings=[embedding] # permite a ChromaDB buscar por similitud semántica, no por palabras exactas
    )