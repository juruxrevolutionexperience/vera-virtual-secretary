# ai-service — Cerebro de Vera

Microservicio Python (FastAPI) que procesa mensajes y genera respuestas de agenda.
Laravel lo llama via HTTP. Esta carpeta es territorio exclusivo de este servicio.

---

## Stack

- Python 3.11+
- FastAPI + Uvicorn
- Groq (llama-3.3-70b) + Gemini como fallback
- ChromaDB o pgvector para embeddings

---

## Arrancar el servicio

```bash
cd ai-service
python -m venv venv
source venv/bin/activate      # Windows: venv\Scripts\activate
pip install -r requirements.txt
uvicorn main:app --reload --port 8001
```

Variables de entorno necesarias (crear `.env` en esta carpeta):
```
GROQ_API_KEY=...
GEMINI_API_KEY=...
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=vera
DB_USER=...
DB_PASSWORD=...
```

---

## El endpoint principal

### POST /process

Laravel llama a este endpoint por cada mensaje entrante.

**Request:**
```json
{
  "user_id": "123",
  "message": "Hola, quiero una reunión el martes",
  "channel": "whatsapp",
  "history": [
    {"role": "user", "content": "Hola"},
    {"role": "assistant", "content": "Hola, soy Vera. ¿En qué puedo ayudarte?"}
  ],
  "brain": {
    "business_name": "Clínica Dr. García",
    "owner_name": "Carlos García",
    "tone": "formal",
    "working_hours": {
      "monday":    {"enabled": true,  "start": "09:00", "end": "18:00"},
      "tuesday":   {"enabled": true,  "start": "09:00", "end": "18:00"},
      "wednesday": {"enabled": true,  "start": "09:00", "end": "18:00"},
      "thursday":  {"enabled": true,  "start": "09:00", "end": "18:00"},
      "friday":    {"enabled": true,  "start": "09:00", "end": "18:00"},
      "saturday":  {"enabled": false},
      "sunday":    {"enabled": false}
    },
    "meeting_duration": 45,
    "buffer_minutes": 15,
    "unknown_response": "Déjame consultarlo y te respondo en breve.",
    "escalation_triggers": ["urgente", "queja", "emergencia"],
    "mode": "supervised"
  }
}
```

**Response — modo normal:**
```json
{
  "response": "Perfecto, tengo disponibilidad el martes a las 10:00 o a las 16:00. ¿Cuál prefieres?",
  "mode": "agenda",
  "escalate": false,
  "confidence": 0.89,
  "suggested_slots": ["2025-01-14T10:00:00", "2025-01-14T16:00:00"],
  "tokens_used": 450
}
```

**Response — escalado:**
```json
{
  "response": null,
  "mode": "escalation",
  "escalate": true,
  "escalation_reason": "queja detectada",
  "confidence": 0.0,
  "tokens_used": 120
}
```

### Modos posibles
| Modo | Descripción |
|---|---|
| `respuesta` | El cliente pregunta algo. Vera responde con info del negocio. |
| `agenda` | El cliente quiere una reunión. Vera propone slots. |
| `transicion` | Empieza como pregunta, deriva a agendar. |
| `escalation` | Trigger detectado. Vera para y notifica al humano. |

---

## Estructura esperada

```
ai-service/
├── main.py                  # FastAPI app, expone POST /process
├── requirements.txt
├── .env                     # No commitear
├── .gitignore
├── rag/
│   ├── embeddings.py        # Genera embeddings (text-embedding-3-small)
│   ├── chunker.py           # Divide documentos en chunks ~500 chars
│   └── search.py            # Búsqueda semántica, score mínimo 0.75, filtro user_id
├── llm/
│   ├── prompt_builder.py    # Construye system prompt completo
│   └── client.py            # Cliente Groq + Gemini fallback
└── modes/
    ├── detector.py          # Detecta modo: respuesta/agenda/transicion
    └── escalation.py        # Detecta triggers de escalado
```

---

## Reglas importantes

- Filtrar SIEMPRE por `user_id` — multitenancy obligatorio
- Score mínimo RAG: **0.75** — si no llega, no usar el fragmento
- Máximo **3 fragmentos** RAG por consulta
- Nunca inventar información — si no sabe, usar `unknown_response` del brain
- El historial incluye los últimos 10 mensajes de la conversación
