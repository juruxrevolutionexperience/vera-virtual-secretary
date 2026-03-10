# Bienvenido al Proyecto — Vera AI Assistant
### Documento de incorporación para Antonio

> **Fecha**: Marzo 2026
> **Estado del MVP**: 94% completado
> **Tu rol**: Becario de IA — optimización, automatización y Python

---

## ÍNDICE

1. [¿Qué es Vera?](#1-qué-es-vera)
2. [Visión y propuesta de valor](#2-visión-y-propuesta-de-valor)
3. [Stack tecnológico](#3-stack-tecnológico)
4. [Estructura del proyecto Laravel](#4-estructura-del-proyecto-laravel)
5. [Arquitectura de clases clave](#5-arquitectura-de-clases-clave)
6. [Cómo funciona la IA en Vera](#6-cómo-funciona-la-ia-en-vera)
7. [APIs y proveedores de IA](#7-apis-y-proveedores-de-ia)
8. [Planes de precios y modelo económico](#8-planes-de-precios-y-modelo-económico)
9. [Sistema de tokens — el núcleo económico](#9-sistema-de-tokens--el-núcleo-económico)
10. [Tu papel: Python y optimización IA](#10-tu-papel-python-y-optimización-ia)
11. [Cómo trabajamos](#11-cómo-trabajamos)
12. [Primeros pasos recomendados](#12-primeros-pasos-recomendados)

---

## 1. ¿Qué es Vera?

**Vera** es un asistente de IA para gestión de emails. Se conecta a tu Gmail (y próximamente Outlook) y trabaja en segundo plano para que nunca te pierdas un email importante, sin que tengas que leer cada correo manualmente.

Lo que Vera hace por el usuario:
- **Analiza** cada email que llega: tono, urgencia, categoría, sentimiento
- **Prioriza** automáticamente (urgente, informativo, spam, factura, etc.)
- **Aprende** el estilo de escritura del usuario y redacta respuestas en su voz
- **Genera resúmenes** semanales y sugerencias de configuración
- **Actúa** de forma autónoma o bajo supervisión del usuario (según configuración)

Piénsalo como un **asistente virtual que lee tus emails antes que tú** y te los presenta ya procesados.

---

## 2. Visión y propuesta de valor

### El problema
El trabajador promedio pierde **2.5 horas al día** gestionando email. Los emails críticos se pierden entre newsletters y cadenas de respuestas. Responder tarda más de lo necesario porque hay que recordar contexto.

### La solución de Vera
Un asistente que:
1. **Lee y clasifica** emails automáticamente al llegar (vía webhook de Gmail)
2. **Tiene contexto** de quién eres, cómo escribes y qué es importante para ti
3. **Propone respuestas** escritas con tu tono y estilo exacto
4. **Aprende** de tus decisiones semana a semana (RAG / memoria contextual)
5. **Es económicamente viable** gracias a una estrategia híbrida reglas+IA

### Mercado objetivo
- Freelancers y autónomos
- Pequeños empresarios / solopreneurs
- Profesionales que gestionan muchos clientes

### Diferenciador clave
No es solo un clasificador de emails. Es un asistente que **aprende tu forma de trabajar** y mejora con el tiempo. La visión a largo plazo es que Vera tome decisiones cada vez más autónomas con la confianza del usuario.

---

## 3. Stack tecnológico

| Capa | Tecnología | Por qué |
|------|-----------|---------|
| **Backend** | Laravel 12 (PHP 8.2+) | Framework maduro, excelente para APIs y jobs async |
| **Frontend** | Blade + Alpine.js + Tailwind CSS | Simple, sin SPA overhead, rápido de desarrollar |
| **Base de datos** | MySQL 8.0 | Relacional, sólido para datos estructurados |
| **Cache y colas** | Redis + Laravel Horizon | Jobs async para no bloquear peticiones |
| **Email sync** | Gmail API + Microsoft Graph | OAuth2, webhooks push (PubSub) |
| **IA principal** | Google Gemini 1.5 Flash | Precio muy bajo, rápido, suficiente para emails |
| **IA secundaria** | Claude / GPT-4 (planificado) | Para emails premium/importantes |
| **Auth** | Laravel Breeze + Google 2FA | Seguridad estándar + 2FA |
| **Billing** | Stripe + Laravel Cashier | Suscripciones mensuales |
| **Deploy** | Docker (Laravel Sail) | Entorno reproducible |

### Laravel — qué necesitas saber si no lo conoces
Laravel es un framework MVC para PHP. Si vienes de Python/Django, los conceptos son muy similares:

| Django | Laravel equiv. |
|--------|---------------|
| `models.py` | `app/Models/*.php` |
| `views.py` | `app/Http/Controllers/*.php` |
| `urls.py` | `routes/web.php` |
| `templates/` | `resources/views/` (Blade) |
| `manage.py migrate` | `php artisan migrate` |
| `celery` | Laravel Horizon (queues) |
| `django-admin` | `php artisan` |

**Comando más usado en este proyecto**: dentro de Docker:
```bash
./vendor/bin/sail artisan <comando>
```

---

## 4. Estructura del proyecto Laravel

```
virtualEmployee/
│
├── app/
│   ├── Console/Commands/          # Comandos artisan (cron jobs, scripts)
│   ├── Http/
│   │   ├── Controllers/           # Lógica de endpoints web
│   │   │   ├── VeraController.php         # Hub central de Vera
│   │   │   ├── VeraUsageController.php    # Uso de tokens y planes
│   │   │   ├── EmailAccountController.php # Conexión Gmail/Outlook
│   │   │   └── Webhooks/                  # Receptores de eventos externos
│   │   └── Middleware/            # Filtros de petición (límites, auth)
│   ├── Jobs/                      # Tareas en cola (async)
│   ├── Models/                    # Modelos de base de datos (Eloquent ORM)
│   ├── Services/                  # Lógica de negocio (aquí está lo interesante)
│   │   ├── AI/
│   │   │   ├── AIGatewayService.php       # ← PUERTA DE ENTRADA a la IA
│   │   │   └── GeminiProvider.php         # ← Implementación Gemini API
│   │   ├── VeraService.php                # ← ORQUESTADOR PRINCIPAL
│   │   ├── VeraWritingStyleService.php    # Extrae estilo de escritura
│   │   ├── VeraUsageService.php           # Controla límites y uso
│   │   └── GmailSyncService.php           # Sincronización Gmail
│   └── Providers/                 # Bootstrapping de servicios
│
├── config/
│   └── vera.php                   # Configuración IA: modelos, límites, features
│
├── database/
│   ├── migrations/                # ~78 archivos de esquema de BD
│   └── seeders/                   # Datos iniciales (planes, etc.)
│
├── resources/views/               # Templates Blade (HTML + PHP)
│   └── vera/                      # UI de Vera
│
├── routes/
│   └── web.php                    # TODAS las rutas del proyecto
│
└── .agents/skills/                # Skills de IA para desarrollo
```

### Convención de nombres en Laravel
- **Modelos**: PascalCase singular → `VeraTokenUsage`, `EmailAccount`
- **Controllers**: PascalCase + Controller → `VeraController`
- **Services**: PascalCase + Service → `AIGatewayService`
- **Jobs**: PascalCase descriptivo → `ProcessEmailsWithVeraJob`
- **Migrations**: snake_case con fecha → `2026_03_01_create_plans_table.php`
- **Rutas en Blade**: snake o kebab-case → `vera.dashboard`, `settings.security`

---

## 5. Arquitectura de clases clave

### El flujo de un email entrante

```
Gmail PubSub Webhook
        ↓
GmailWebhookController.php
        ↓
IncrementalSyncEmailsJob (queue)
        ↓
GmailSyncService::syncNewEmails()
        ↓
ProcessEmailsWithVeraJob (queue)
        ↓
VeraService::analyzeEmail()          ← AQUÍ EMPIEZA LA IA
        ↓
    ┌───────────────────────────┐
    │ PASO 1: Reglas base (free) │  → Siempre, sin coste
    │ PASO 2: ¿Necesita IA?     │  → 80% no la necesita
    │ PASO 3: Límite del plan   │  → Verificar cuota del usuario
    │ PASO 3.5: quickClassify() │  → 80 tokens (screening rápido)
    │ PASO 4: Full AI analysis  │  → 300-600 tokens (solo si procede)
    └───────────────────────────┘
        ↓
AIGatewayService::analyze()
        ↓
GeminiProvider::callAPI()
        ↓
Resultado guardado en emails + vera_token_usage
```

### Modelos de base de datos importantes

| Modelo | Tabla | Para qué sirve |
|--------|-------|----------------|
| `User` | `users` | Usuario + plan_type + billing |
| `Email` | `emails` | Email + todos los campos de análisis Vera |
| `EmailAccount` | `email_accounts` | Cuenta Gmail/Outlook conectada |
| `VeraConfig` | `vera_configs` | Configuración personal de Vera del usuario |
| `VeraTokenUsage` | `vera_token_usage` | Cada llamada a la IA (tokens, coste, modelo) |
| `Plan` | `plans` | Planes de suscripción y sus límites |
| `VeraMemoryChunk` | `vera_memory_chunks` | Base de conocimiento RAG del usuario |

### Campos clave del modelo `Email` (lo que Vera guarda)

```php
// Análisis básico
vera_category          // Categoria (work, client, newsletter...)
vera_priority          // Prioridad (urgent, high, normal, low)
vera_sentiment         // Sentimiento general
vera_summary           // Resumen en 2-3 frases
vera_action_required   // reply, forward, archive, none

// Campos enriquecidos (nuevos)
vera_sentimiento_tono  // Tono emocional (formal, presionado, urgente...)
vera_badge_sugerido    // Etiqueta visual (Trabajo, Cliente, Factura...)
vera_suggested_reply   // Borrador de respuesta en tu estilo
vera_confidence        // Confianza del modelo (0.0 - 1.0)
vera_processed         // Boolean: ya fue analizado
```

---

## 6. Cómo funciona la IA en Vera

### Estrategia híbrida: el núcleo del ahorro de costes

Vera NO analiza con IA cada email. La estrategia tiene 4 niveles:

```
Nivel 1 — REGLAS BASE (gratis, siempre)
├── Newsletter detectada por headers → auto-categorizar
├── Remitente en lista importante → prioridad alta
├── Palabras clave en asunto → urgente
└── Resultado: ~60-70% de emails resueltos sin IA

Nivel 2 — QUICK CLASSIFY (~80 tokens, ~€0.000015/email)
├── ¿Necesita respuesta? ¿Es urgente?
├── Modelo: Gemini Flash en modo rápido
└── Resultado: filtra otro ~15% sin análisis full

Nivel 3 — FULL ANALYSIS (~300-600 tokens, ~€0.0001/email)
├── Análisis completo: sentimiento, tono, badge, reply draft
├── Solo emails que realmente lo necesitan (~15-20%)
└── Resultado: máxima calidad donde importa

Nivel 4 — APRENDIZAJE (weekly, gratis en batch)
├── Reanaliza patrones de la semana
├── Genera sugerencias para el usuario
└── Actualiza memoria RAG
```

### RAG — Memoria de Vera

Vera guarda fragmentos de aprendizaje en `vera_memory_chunks`:
- "Este usuario siempre responde en menos de 2 horas a clientes"
- "Los emails de facturación son siempre urgentes para él"
- "Su estilo es formal pero cercano, sin emojis"

Estos chunks se inyectan en el prompt cuando se analiza un email nuevo, dando contexto al modelo.

### Prompt engineering (en VeraService.php)

Los prompts están en `buildAnalysisPrompt()`. Vera pide a Gemini una respuesta en JSON estricto:

```json
{
  "category": "work",
  "priority": "high",
  "sentiment": "neutral",
  "summary": "...",
  "action_required": "reply",
  "sentimiento_tono": "formal",
  "badge_sugerido": "Cliente",
  "suggested_reply": "...",
  "aprender_de_esto": "...",
  "confidence": 0.92
}
```

El campo `aprender_de_esto` va directo a `vera_memory_chunks` para RAG futuro.

### Circuit Breaker

Si Gemini falla 3 veces seguidas → estado OPEN → Vera cae a análisis por reglas únicamente (sin IA). Después de 5 minutos → HALF-OPEN → reintenta. Esto garantiza que el sistema funciona siempre, aunque la IA no esté disponible.

---

## 7. APIs y proveedores de IA

### Arquitectura actual: un gateway, múltiples proveedores

```php
// app/Services/AI/AIGatewayService.php
// Esto es la abstracción que usa VeraService

$result = $this->aiGateway->analyze($emailContent, $context);
// El gateway decide qué proveedor usar internamente
```

El gateway actualmente soporta:
- **Gemini Flash** (activo, barato, rápido)
- **OpenAI GPT-4** (configurado pero no activo)
- **Anthropic Claude** (configurado pero no activo)

### Por qué Gemini Flash para el MVP

| Métrica | Gemini 1.5 Flash | GPT-4o | Claude Sonnet |
|---------|-----------------|---------|---------------|
| Tokens entrada (1M) | $0.075 | $2.50 | $3.00 |
| Tokens salida (1M) | $0.30 | $10.00 | $15.00 |
| Velocidad | Muy rápido | Rápido | Rápido |
| Calidad emails | Suficiente | Alta | Alta |
| **Ideal para** | Volumen alto | Casos premium | Casos complejos |

Con **2.004 tokens/email** de media (medidos en producción real):
- Gemini Flash: ~**€0.0004/email**
- GPT-4o: ~**€0.025/email** (62x más caro)
- Claude Sonnet: ~**€0.030/email** (75x más caro)

### Plan futuro: modelo dual

Para emails de alta prioridad (identificados en PASO 3.5), escalar a Claude Sonnet o GPT-4o. Esto está en `PRODUCCION_PENDIENTE.md` como próxima feature. Antonio puede ser clave aquí.

---

## 8. Planes de precios y modelo económico

### Los planes actuales

| Plan | Emails/mes | Precio | Coste IA/mes | Margen bruto |
|------|-----------|--------|--------------|-------------|
| **Basic** | 100 | €X | ~€0.04 | Alto |
| **Starter** | 500 | €X | ~€0.20 | Alto |
| **Pro** | 2.000 | €X | ~€0.80 | Alto |
| **Enterprise** | Personalizado | €X | Variable | Negociable |

> Los precios de venta están en definición. El coste de API es tan bajo que el margen es enorme.

### Modelo de límites

Actualmente Vera usa **emails/mes** como unidad de límite (simple, entendible por el usuario). El plan futuro es migrar a un sistema de **créditos** donde cada modelo IA consume diferente número de créditos:
- Gemini Flash: 1 crédito / 1.000 tokens
- Claude Sonnet: 20 créditos / 1.000 tokens

Esto permite ofrecer calidad diferenciada por plan sin complicar la UI.

### Escalabilidad económica

Con Gemini Flash gratuito (límite gratuito aguanta ~15 usuarios con 100 emails/día), los primeros usuarios son casi sin coste. La escalabilidad económica es muy favorable.

---

## 9. Sistema de tokens — el núcleo económico

### VeraTokenUsage — cada llamada registrada

Cada vez que Vera llama a la IA, se registra en `vera_token_usage`:

```
user_id | email_id | action_type         | tokens_prompt | tokens_completion | cost_eur | model
--------|----------|---------------------|---------------|-------------------|----------|------
1       | 245      | email_analysis      | 450           | 180               | 0.000180 | gemini-1.5-flash
1       | null     | writing_style       | 3200          | 800               | 0.001200 | gemini-1.5-flash
```

### Tipos de acciones rastreadas

- `email_analysis` — Análisis completo de email
- `quick_classify` — Clasificación rápida (Nivel 2)
- `writing_style_analysis` — Extracción de estilo
- `weekly_reanalysis` — Resumen semanal
- `chat_message` — Chat con Vera

### Dashboard de uso

`/vera/tokens` muestra al usuario:
1. Consumo del mes (progreso vs límite del plan)
2. Historial de llamadas
3. Coste real en € (transparencia total)
4. Calculadora de pricing (cuánto pagas en API por tus emails)
5. Comparativa de planes

---

## 10. Tu papel: Python y optimización IA

### Por qué Python aquí

El backend es Laravel/PHP, pero Python domina el mundo de IA/ML. Tu rol es crear microservicios o scripts Python que complementen Laravel para:

1. **Optimización de prompts** — Reducir tokens manteniendo calidad
2. **Análisis de datos de uso** — Los datos están en MySQL, Python es ideal para analizar
3. **Fine-tuning o embeddings** — Para mejorar el RAG de Vera
4. **Scripts de testing IA** — Evaluar calidad de respuestas Gemini vs Claude vs GPT
5. **Pipeline de ML** — Si queremos entrenar modelos propios para clasificación

### Lo que queda por hacer (donde puedes aportar directamente)

#### A. Optimización de prompts (PRIORIDAD ALTA)

Los prompts actuales en `VeraService::buildAnalysisPrompt()` son funcionales pero no están optimizados. Objetivos:
- Reducir de ~450 tokens de entrada → ~200 sin pérdida de calidad
- A/B testing de prompts: medir calidad con diferentes estructuras
- Crear un benchmark: 100 emails reales → evaluar accuracy de categorización

**Herramienta sugerida**: Jupyter Notebook + pandas + MySQL connector

```python
# Idea: comparar outputs de diferentes prompts
import pandas as pd
import mysql.connector

# Cargar emails reales de la BD
emails = pd.read_sql("SELECT * FROM emails WHERE vera_processed=1 LIMIT 200", conn)

# Evaluar diferentes estrategias de prompt
# Medir: tokens usados, accuracy, tiempo
```

#### B. Modelo de clasificación propio (MEDIO PLAZO)

Con suficientes emails etiquetados (ya tenemos cientos en la BD), podemos entrenar un clasificador ligero:
- Input: asunto + from + primeros 200 chars del body
- Output: category + priority (sin llamar a Gemini)
- Coste: €0 por email una vez entrenado

Tecnologías: `scikit-learn` / `fastText` / `sentence-transformers`

Este clasificador podría **reemplazar el Nivel 2** (quick classify de 80 tokens) con coste cero.

#### C. Sistema de embeddings para RAG (MEDIO PLAZO)

Actualmente `vera_memory_chunks` usa búsqueda por texto. Con embeddings podemos hacer búsqueda semántica real:
- Usar `sentence-transformers` para vectorizar los chunks
- Guardar vectores en MySQL o migrar a pgvector/Pinecone
- Mejorar la relevancia del contexto inyectado en prompts

#### D. Evaluación automática de calidad (INMEDIATO)

Crea un script que:
1. Tome 50 emails procesados por Vera
2. Analice si la categorización tiene sentido (con métricas simples)
3. Detecte patrones de error (¿dónde falla Vera más?)
4. Genere un reporte en HTML o notebook

Esto nos dará datos reales para priorizar mejoras.

#### E. Análisis de rentabilidad (INMEDIATO)

Con los datos de `vera_token_usage`:
- ¿Qué tipo de emails consumen más tokens? ¿Por qué?
- ¿Cuál es la distribución de tokens por acción?
- ¿Dónde está el 20% de emails que consume el 80% de los tokens?
- ¿Qué patrones de usuario tienen mayor consumo?

```python
# Análisis de Pareto en tokens
import pandas as pd

df = pd.read_sql("SELECT * FROM vera_token_usage", conn)
df.groupby('action_type')['tokens_total'].describe()
df.groupby('user_id')['cost_eur'].sum().sort_values(ascending=False)
```

### Integración Python ↔ Laravel

Hay dos formas de integrar Python en este stack:

**Opción A: Scripts independientes** (más simple, ya mismo)
- Scripts Python que leen/escriben en MySQL directamente
- Perfectos para análisis y experimentos
- No necesitan tocar el código Laravel

**Opción B: Microservicio FastAPI** (futuro)
- API REST con FastAPI que Laravel llama vía HTTP
- Para cuando tengamos modelos propios en producción
- Ejemplo: clasificador ligero sirviendo predicciones a Laravel

Por ahora empieza con Opción A. Si surge algo que deba estar en producción, lo empaquetamos en Opción B.

---

## 11. Cómo trabajamos

### Flujo de desarrollo

1. **Rama**: trabajamos en `main` (MVP todavía, simplificamos)
2. **Entorno**: Docker con Laravel Sail
3. **Comandos básicos**:
   ```bash
   # Levantar entorno
   ./vendor/bin/sail up -d

   # Ejecutar migraciones
   ./vendor/bin/sail artisan migrate

   # Ejecutar un comando artisan
   ./vendor/bin/sail artisan vera:weekly-reanalysis

   # Ver logs de colas
   ./vendor/bin/sail artisan horizon
   ```

4. **Base de datos**: MySQL accesible desde localhost:3306 (ver docker-compose.yml para credenciales)

### Convenciones de código

- **PHP**: PSR-12, clases en PascalCase, métodos en camelCase
- **Python**: PEP8, funciones en snake_case
- **SQL**: snake_case para tablas y columnas
- **Commits**: mensajes descriptivos en español o inglés
- **Colores UI**: purple-600 para acciones principales, text-gray-900 sobre fondo blanco

### Qué NO hacer sin confirmación

- `migrate:fresh` (borra todos los datos)
- Modificar tablas de producción sin backup
- Cambiar proveedores de IA sin revisar costes
- Push a main sin revisar impacto

---

## 12. Primeros pasos recomendados

### Semana 1: Orientación

1. **Clona y levanta el entorno** — Sigue el README, levanta Docker, corre las migraciones
2. **Explora la BD** — Entiende las tablas principales (`emails`, `vera_token_usage`, `vera_configs`)
3. **Lee estos archivos clave**:
   - `app/Services/VeraService.php` — El cerebro
   - `app/Services/AI/AIGatewayService.php` — La puerta a la IA
   - `config/vera.php` — La configuración
   - `tokensImplementaciones.md` — Historial de lo implementado

### Semana 2: Primera contribución

**Tarea sugerida**: Script de análisis de calidad

Crea un Jupyter Notebook que:
1. Conecte a la BD MySQL
2. Cargue los últimos 100 emails procesados por Vera
3. Analice la distribución de categorías, prioridades, sentimientos
4. Calcule el coste medio por email y por tipo de acción
5. Identifique outliers (emails que consumieron muchos tokens)
6. Genere visualizaciones con matplotlib/seaborn

Esto nos dará un baseline de cómo está funcionando la IA actualmente.

### Semana 3+: Proponer y ejecutar

Con el conocimiento del proyecto, propón qué mejorarías primero en la parte de IA. Las áreas con más impacto económico son:
- Reducir tokens en prompts sin perder calidad
- Clasificador propio para reemplazar quickClassify
- Mejora del sistema RAG con embeddings reales

---

## Resumen rápido para empezar

```
Vera = Asistente IA de emails
Stack = Laravel (PHP) + Gemini API + Redis + MySQL + Docker
Objetivo económico = gastar lo mínimo en IA, maximizar valor al usuario
Tu rol = Python + análisis de datos + optimización de IA
Archivo más importante = app/Services/VeraService.php
Comando más usado = ./vendor/bin/sail artisan <algo>
```

---

**Cualquier duda, pregunta. Bienvenido al equipo.**

---

*Documento generado: Marzo 2026 — Virtual Employee / Vera AI*
