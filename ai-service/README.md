# ai-service — Cerebro de Vera

Microservicio Python (FastAPI) que procesa mensajes y genera respuestas de Vera.
Laravel lo llama vía HTTP. **Esta carpeta es tu territorio exclusivo.**

---

## Arrancar el entorno completo

Necesitas **dos terminales** abiertas en la raíz del proyecto.

### Terminal 1 — Docker (Laravel + MySQL + Redis)

```bash
./vendor/bin/sail up -d
```

Esto levanta: Laravel (puerto 80), MySQL (3306), Redis, Horizon (colas), Scheduler (emails cada 5 min), phpMyAdmin (8080).

> Solo hace falta la primera vez que enciendes el PC o después de un reinicio.

---

### Terminal 2 — ai-service (Cerebro de Vera)

```bash
cd ai-service
bash start.sh
```

El script crea el entorno virtual automáticamente, instala dependencias y levanta el servidor en el **puerto 8001**.

> **No cerrar esta terminal** — si se cierra, Vera deja de razonar y cae al modo placeholder.

---

### Verificar que todo está levantado

```bash
# Laravel responde
curl http://localhost

# ai-service responde
curl http://localhost:8001/health
```

`{"status":"ok","service":"vera-ai-service","version":"1.0.0"}` = todo correcto.

---

### Parar todo

```bash
# Docker
./vendor/bin/sail down

# ai-service: Ctrl+C en la Terminal 2
```

---

## Cómo funciona el flujo completo

```
Gmail → Laravel (ProcessIncomingMessage.php)
    → POST http://localhost:8001/process       ← aquí entras tú
    ← { response, mode, escalate, ... }
Laravel → Google Calendar (slots reales)
Laravel → Gmail (envía respuesta al cliente)
```

Laravel hace todo el trabajo de Calendar y email. Tu trabajo es que **la respuesta de Vera sea buena**: tono, razonamiento, naturalidad, intención detectada correctamente.

---

## Tu espacio de trabajo

```
ai-service/
├── llm/
│   ├── prompt_builder.py    ← AQUÍ: system prompt, instrucciones, personalidad de Vera
│   └── client.py            ← Groq (llama-3.3-70b) + Gemini fallback. No tocar.
├── modes/
│   ├── detector.py          ← Detecta si el mensaje es agenda/respuesta/transicion
│   └── escalation.py        ← Detecta triggers urgentes (queja, emergencia, etc.)
├── rag/
│   ├── embeddings.py        ← ChromaDB local, SentenceTransformers ONNX. No tocar.
│   ├── chunker.py           ← Divide el cerebro del cliente en chunks. No tocar.
│   └── search.py            ← Búsqueda semántica. No tocar.
└── main.py                  ← Endpoints FastAPI. No tocar salvo que añadas endpoints.
```

**Lo que más importa que mejores:**
- `prompt_builder.py` — calidad del prompt = calidad de Vera
- `detector.py` — detección correcta del modo (`agenda` vs `respuesta`)
- `escalation.py` — qué mensajes deben escalar al humano

---

## El endpoint principal

### POST /process

**Request que recibes de Laravel:**
```json
{
  "user_id": "1",
  "message": "Hola, quiero una reunión el martes",
  "channel": "email",
  "history": [
    {"role": "user", "content": "mensaje anterior"},
    {"role": "assistant", "content": "respuesta anterior de Vera"}
  ],
  "brain": {
    "business_name": "Jurux Revolution",
    "owner_name": "Jose",
    "tone": "profesional y cercano",
    "language": "es",
    "timezone": "Europe/Madrid",
    "working_hours": {
      "monday":    {"enabled": true,  "start": "09:00", "end": "18:00"},
      "tuesday":   {"enabled": true,  "start": "09:00", "end": "18:00"},
      "wednesday": {"enabled": true,  "start": "09:00", "end": "18:00"},
      "thursday":  {"enabled": true,  "start": "09:00", "end": "18:00"},
      "friday":    {"enabled": true,  "start": "09:00", "end": "18:00"},
      "saturday":  {"enabled": false},
      "sunday":    {"enabled": false}
    },
    "meeting_duration": 30,
    "buffer_minutes": 15,
    "unknown_response": "Déjame consultarlo y te respondo en breve.",
    "escalation_triggers": ["urgente", "queja", "emergencia"],
    "mode": "supervised"
  }
}
```

**Response que debes devolver a Laravel:**
```json
{
  "response": "Claro, con mucho gusto. Aquí tienes los huecos disponibles:",
  "mode": "agenda",
  "escalate": false,
  "confidence": 0.9,
  "suggested_slots": [],
  "tokens_used": 0
}
```

### Modos posibles

| Modo | Cuándo usarlo |
|---|---|
| `agenda` | El cliente quiere una reunión. Laravel añadirá los slots reales del Calendar. |
| `respuesta` | El cliente pregunta algo del negocio. Vera responde con info del RAG. |
| `transicion` | Empieza como pregunta pero deriva a querer reunión. |
| `escalation` | Trigger urgente detectado. Poner `escalate: true`. |

> **Importante sobre `mode: agenda`:** Cuando devuelves este modo, Laravel consulta Google Calendar y añade los slots disponibles reales al final del mensaje de Vera. Tú no propones horarios concretos — el Calendar los da. Vera solo pone el texto conversacional previo.

---

## Probar sin tocar Laravel

```bash
curl -s -X POST http://localhost:8001/process \
  -H "Content-Type: application/json" \
  -d '{
    "user_id": "1",
    "message": "Hola, quiero una reunión",
    "channel": "email",
    "history": [],
    "brain": {
      "business_name": "Test",
      "owner_name": "Jose",
      "tone": "profesional",
      "language": "es",
      "timezone": "Europe/Madrid",
      "working_hours": {"monday": {"enabled": true, "start": "09:00", "end": "18:00"}},
      "meeting_duration": 30,
      "buffer_minutes": 15,
      "escalation_triggers": [],
      "mode": "supervised"
    }
  }' | python3 -m json.tool
```

Health check:
```bash
curl http://localhost:8001/health
```

---

## Memoria (RAG con ChromaDB)

El cerebro de cada cliente (FAQs, info del negocio, estilo de comunicación) se guarda en ChromaDB local. Se busca automáticamente en cada mensaje para enriquecer el prompt.

- Laravel sincroniza a ChromaDB cuando el cliente edita su cerebro
- La búsqueda es semántica, score mínimo 0.75
- Siempre filtrado por `user_id` — multitenancy

---

## Reglas importantes

- **Nunca inventar horarios** — el Calendar los da siempre
- **Nunca inventar info del negocio** — usar `unknown_response` si no hay contexto en RAG
- **Siempre filtrar por `user_id`** — cada cliente es aislado
- **Historial máximo 10 mensajes** — ya viene limitado de Laravel
