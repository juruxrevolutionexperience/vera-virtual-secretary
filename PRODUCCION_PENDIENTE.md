# Checklist de Producción — Vera AI

Cosas que están deliberadamente simplificadas para el MVP
y que hay que implementar antes/durante el lanzamiento a producción.

---

## IA y Modelos

### [ ] Dual Model — Modelo Pro solo para emails importantes
**Estado actual:** Gemini Flash para el 100% de los análisis.
**En producción:** Si el email tiene `priority = urgent` + `action_required = reply`,
escalar a un modelo más potente (Claude 3.5 Sonnet o Gemini Pro) para la redacción final.

**Dónde tocar:**
- `app/Services/VeraService.php` → método `analyzeWithAI()`
- `app/Services/AI/AIGatewayService.php` → añadir provider Claude/GPT
- `config/vera.php` → añadir `pro_model` y umbral de escalado

**Por qué esperar:** Gemini Flash da buena calidad para MVP.
Implementar cuando tengamos datos reales de qué emails "fallan".

---

### [ ] Sistema de Créditos como moneda interna (multi-modelo)
**Estado actual:** El límite se gestiona en emails/mes con Gemini Flash como único modelo.
El widget del dashboard y todos los gates usan emails como unidad. Implementado y unificado (03/03/2026).

**Cuándo implementar:** Cuando se añada un segundo modelo de IA (Claude Sonnet, GPT-4o, etc.).
El problema es que 1.000 tokens de Gemini Flash cuestan ~$0.08 pero 1.000 tokens de Claude Sonnet cuestan ~$3.00 (37x más). Con un solo modelo, emails es la métrica correcta. Con dos modelos, necesitas normalizar el coste.

**Cómo implementarlo:**
1. Crear tabla `ai_models`: `{ slug, name, credits_per_1k_tokens, cost_per_1m_tokens }`
   - `gemini-flash` → 1 crédito / 1k tokens
   - `claude-sonnet` → 20 créditos / 1k tokens
   - `gpt-4o` → 15 créditos / 1k tokens
2. Migrar `plans.email_limit` → añadir `plans.credit_limit` (mantener email_limit para UI)
3. Añadir `users.monthly_credits_used` / `monthly_credits_limit`
4. En `VeraTokenUsage::record()` calcular: `$creditos = ($tokens / 1000) * $modelo->credits_per_1k_tokens`
5. El gate (`VeraEmailLimitMiddleware`) pasa a comprobar créditos en lugar de emails
6. El widget del dashboard muestra créditos o emails equivalentes

**Dónde tocar:**
- `app/Services/AI/AIGatewayService.php` → añadir `ClaudeProvider`, `OpenAIProvider`
- `app/Models/VeraTokenUsage.php` → método `record()` + cálculo de créditos
- `app/Http/Middleware/VeraEmailLimitMiddleware.php` → gate por créditos
- `resources/views/components/tokens-widget.blade.php` → mostrar créditos
- Nueva migración: `create_ai_models_table` + `add_credits_to_users_table`

**Por qué esperar:** Con un solo modelo (Gemini Flash), emails es más claro para el usuario
y el coste es predecible. Implementar solo cuando se active un segundo proveedor de IA.

> ✅ **Decisión confirmada 10/03/2026:** Se evaluó implementarlo ahora y se descartó.
> Meter un sistema de créditos para un solo modelo es complejidad sin beneficio real.
> El campo `plans.token_limit` ya está reservado en BD para cuando llegue ese momento.

---

### [ ] Power Packs — créditos extra puntuales (upselling)
**Estado actual:** No existe. El usuario que llega al límite solo puede cambiar de plan.
**En producción:** Vender paquetes de créditos extra de pago único sin cambiar de plan.
Ejemplo: "500 análisis extra por €2.99" → Stripe one-time payment → suma emails/créditos al usuario.

**Por qué es valioso:** Es upselling directo. El usuario que llega al límite antes de fin de mes
está motivado para comprar. Es más fácil vender €2.99 extra que convencer de subir de plan.

**Depende de:** Sistema de créditos (ver sección anterior). Implementar junto con créditos.

**Dónde tocar:**
- `app/Http/Controllers/BillingController.php` → nueva acción `buyPowerPack()`
- Stripe → crear producto de pago único
- `users` → campo `extra_emails` o `extra_credits` que se suma al límite del plan

---

## Negocio y Facturación

### [ ] Precio por correo adicional (Stripe overage)
**Estado actual:** Los usuarios tienen un límite de emails/mes por plan.
Si se pasan, simplemente no se procesan más emails ese mes.
**En producción:** Cobrar automáticamente un precio por correo adicional
cuando superen el límite del plan.

**Dónde tocar:**
- `app/Services/VeraUsageService.php` → cuando se supere el límite, crear un `Stripe Meter Event`
- `app/Models/User.php` → añadir `overage_price_per_email` por plan
- Panel de usuario → mostrar coste estimado de exceso en tiempo real

**Por qué esperar:** Necesita usuarios de pago reales para validar el modelo de precios.

---

---

## IA — Mejoras con Python

> 💡 **Nota:** Estas tareas pueden ser iniciadas por cualquiera del equipo.
> Antonio (incorporación semana del 16/03) tiene el perfil ideal para llevarlas,
> pero perfectamente pueden arrancarse antes si hay tiempo y ganas.

### [ ] RAG con embeddings vectoriales (LangChain + FAISS)
**Estado actual:** `vera_memory_chunks` usa búsqueda por texto (`LIKE`). Si el chunk dice
"responde rápido a clientes" y el email habla de "urgencia con un cliente", no conecta.

**Mejora propuesta:** Convertir los chunks a vectores semánticos. La búsqueda pasaría a ser
por similitud de significado, no de palabras exactas. Resultado: Vera inyecta contexto
más relevante en cada análisis → respuestas de mayor calidad.

**Plan de implementación:**
1. Jupyter Notebook: cargar `vera_memory_chunks` desde MySQL, generar embeddings con
   `sentence-transformers`, indexar con FAISS
2. Comparar calidad: mismos emails analizados con RAG texto vs RAG vectorial
3. Si mejora, empaquetar como microservicio FastAPI que Laravel llama vía HTTP

**Stack Python:** `langchain-google-genai`, `sentence-transformers`, `faiss-cpu`, `fastapi`

**Por qué esperar:** Primero validar con usuarios reales que el RAG actual falla.
Si los usuarios están satisfechos con las respuestas de Vera, no es prioritario.

---

_Archivo creado: 01/03/2026_
_Actualizar con cada decisión de "esto lo dejamos para producción"._
