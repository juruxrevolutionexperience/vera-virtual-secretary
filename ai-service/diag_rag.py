"""Diagnóstico RAG: muestra qué recupera ChromaDB para una query y con qué score.
Uso: python diag_rag.py
Borrar tras diagnosticar."""
import sys
from rag.embeddings import get_collection

USER_ID = "2"
QUERY = "¿cuánto cuesta Vera y con qué se conecta? precio coste tarifa servicio"

col = get_collection(USER_ID)
print(f"Colección user_{USER_ID} — total chunks: {col.count()}")
print(f"Query: {QUERY}\n")

res = col.query(
    query_texts=[QUERY],
    n_results=min(6, col.count()),
    where={"active": True},
    include=["documents", "distances", "metadatas"],
)

docs = res["documents"][0]
dists = res["distances"][0]
metas = res["metadatas"][0]

print(f"Resultados devueltos: {len(docs)} (umbral actual: score >= 0.65)\n")
for d, dist, meta in zip(docs, dists, metas):
    score = 1 - dist
    passa = "✅ PASA" if score >= 0.65 else "❌ descartado"
    print(f"[{meta.get('type','?')}] distance={dist:.3f}  score(1-dist)={score:.3f}  {passa}")
    print(f"    {d[:90]}...")
    print()
