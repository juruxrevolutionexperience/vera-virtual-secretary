# Sistema de Gestión de Planes y Tokens - Vera

> **Objetivo**: Control preciso del consumo de tokens de Gemini API, gestión de planes de suscripción y optimización de costos.

---

## 📋 Índice

1. [Arquitectura del Sistema](#arquitectura-del-sistema)
2. [Estructura de Base de Datos](#estructura-de-base-de-datos)
3. [Consumo de Tokens por Acción](#consumo-de-tokens-por-accion)
4. [Planes y Límites](#planes-y-limites)
5. [Vista de Usuario (Emails)](#vista-de-usuario-emails)
6. [Vista Admin (Tokens Analytics)](#vista-admin-tokens-analytics)
7. [Métrica Clave: Email Procesado](#metrica-clave-email-procesado)
8. [Sistema de Alertas](#sistema-de-alertas)
9. [Implementación Paso a Paso](#implementacion-paso-a-paso)
10. [Casos Edge y Reglas de Negocio](#casos-edge-y-reglas-de-negocio)
11. [Estimación de Costos](#estimacion-de-costos)
12. [Optimización Futura](#optimizacion-futura)

---

## 🏗️ Arquitectura del Sistema

```
┌─────────────────────────────────────────────────────┐
│  Usuario realiza acción (analizar email, generar   │
│  respuesta, chat, etc.)                             │
└──────────────────┬──────────────────────────────────┘
                   │
                   ▼
┌─────────────────────────────────────────────────────┐
│  VeraEmailLimitMiddleware                           │
│  - Verifica límites del plan (en EMAILS)            │
│  - Rechaza si excede límite mensual                 │
│  - Registra intento bloqueado                       │
└──────────────────┬──────────────────────────────────┘
                   │ ✅ Permitido
                   ▼
┌─────────────────────────────────────────────────────┐
│  GeminiProvider::analyze()                          │
│  - Llama a Gemini API                               │
│  - Recibe usageMetadata de Gemini:                  │
│    • promptTokenCount                               │
│    • candidatesTokenCount                           │
│    • totalTokenCount                                │
└──────────────────┬──────────────────────────────────┘
                   │
                   ▼
┌─────────────────────────────────────────────────────┐
│  VeraTokenUsage::record()                           │
│  - Guarda registro en BD:                           │
│    • user_id, email_id, action_type                 │
│    • prompt_tokens, completion_tokens, total        │
│    • model, cost, metadata (subject, from, etc.)    │
└──────────────────┬──────────────────────────────────┘
                   │
                   ▼
┌─────────────────────────────────────────────────────┐
│  VeraUsageService::calculateUsage()                 │
│  - Vista Usuario: Emails procesados este mes        │
│  - Vista Admin: Tokens detallados por acción        │
│  - Compara con límites del plan                     │
│  - Genera analytics y recomendaciones               │
└─────────────────────────────────────────────────────┘
```

### Flujo de decisión: ¿Usar IA o Reglas?

```
Email recibido
    │
    ▼
¿Usuario tiene límite disponible?
    │
    ├─ NO → Usar solo REGLAS (gratis) → Marcar email con flag 'ai_skipped'
    │
    ├─ SÍ → Verificar si email necesita IA
    │         │
    │         ├─ Email simple/newsletter → REGLAS (gratis)
    │         │
    │         └─ Email complejo/importante → IA (consume tokens)
    │                                          │
    │                                          ▼
    │                                      Registrar uso
    │                                      Decrementar contador
```

---

## 🗄️ Estructura de Base de Datos

### Tabla: `vera_token_usage`

```sql
CREATE TABLE vera_token_usage (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    action_type ENUM('email_analysis', 'response_generation', 'chat_interaction') NOT NULL,
    email_id BIGINT UNSIGNED NULL,

    -- Tokens consumidos (datos de Gemini API)
    prompt_tokens INT UNSIGNED NOT NULL DEFAULT 0,
    completion_tokens INT UNSIGNED NOT NULL DEFAULT 0,
    total_tokens INT UNSIGNED NOT NULL DEFAULT 0,

    -- Metadata del modelo
    model VARCHAR(100) NOT NULL DEFAULT 'gemini-1.5-flash',
    cost DECIMAL(10, 6) NOT NULL DEFAULT 0.000000,

    -- Datos adicionales para analytics
    metadata JSON NULL COMMENT 'Subject, from, content_length, etc.',

    -- Timestamps
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    -- Índices
    INDEX idx_user_created (user_id, created_at),
    INDEX idx_user_action (user_id, action_type),
    INDEX idx_email (email_id),
    INDEX idx_created_at (created_at),

    -- Foreign keys
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (email_id) REFERENCES emails(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Ejemplo de registro:**
```json
{
    "id": 1,
    "user_id": 5,
    "action_type": "email_analysis",
    "email_id": 1234,
    "prompt_tokens": 234,
    "completion_tokens": 87,
    "total_tokens": 321,
    "model": "gemini-1.5-flash",
    "cost": 0.000032,
    "metadata": {
        "subject": "Consulta sobre proyecto X",
        "from": "client@example.com",
        "content_length": 1456,
        "analysis_duration_ms": 1234
    },
    "created_at": "2024-12-20 14:35:22"
}
```

### Campos nuevos en tabla `users`

```sql
ALTER TABLE users ADD COLUMN plan_type ENUM('basic', 'starter', 'pro') NOT NULL DEFAULT 'basic';
ALTER TABLE users ADD COLUMN monthly_email_limit INT UNSIGNED NOT NULL DEFAULT 100;
ALTER TABLE users ADD COLUMN billing_cycle_start DATE NULL;
ALTER TABLE users ADD COLUMN plan_upgraded_at TIMESTAMP NULL;
ALTER TABLE users ADD COLUMN plan_metadata JSON NULL COMMENT 'Historial de planes, promociones, etc.';
```

### Vista materializada (opcional, para performance)

```sql
CREATE VIEW vera_monthly_usage AS
SELECT
    user_id,
    DATE_FORMAT(created_at, '%Y-%m') as month,
    action_type,
    COUNT(*) as action_count,
    SUM(total_tokens) as total_tokens,
    AVG(total_tokens) as avg_tokens_per_action,
    SUM(cost) as total_cost
FROM vera_token_usage
GROUP BY user_id, month, action_type;
```

---

## 📊 Consumo de Tokens por Acción

Basándonos en el código actual de Vera:

### 1. Análisis de Email (`email_analysis`)

**Ubicación**: `app/Services/VeraService.php::analyzeWithAI()`

**Desglose de tokens:**

| Componente | Tokens | Detalle |
|------------|--------|---------|
| System Prompt | ~150 tokens | Personalidad de Vera, instrucciones, formato JSON |
| Email metadata | ~50 tokens | From, To, Subject |
| Email content | 150-500 tokens | Limitado a 2000 caracteres (~500 tokens max) |
| Response JSON | 50-100 tokens | Summary, sentiment, priority, topics, action |
| **TOTAL** | **400-800 tokens** | Promedio: **~500 tokens** |

**Código relevante:**
```php
// app/Services/VeraService.php:226-240
protected function getEmailContent(Email $email): string
{
    $content = $email->body_text ?? strip_tags($email->body_html ?? '');
    $content = preg_replace('/\n{3,}/', "\n\n", $content);

    // 🎯 OPTIMIZACIÓN: Limitar a 2000 chars para ahorrar tokens
    if (strlen($content) > 2000) {
        $content = substr($content, 0, 2000) . '...';
    }

    return trim($content);
}
```

### 2. Generación de Respuesta (`response_generation`)

**Ubicación**: `app/Services/VeraResponseGenerator.php::generateWithAI()`

**Desglose de tokens:**

| Componente | Tokens | Detalle |
|------------|--------|---------|
| System Prompt | ~300 tokens | Estilo del usuario, greetings, closings, formalidad, instrucciones |
| Email original | 150-500 tokens | Email a responder (limitado 1500 chars) |
| Thread context | 100-300 tokens | Emails previos del thread (límite 5 emails) |
| User prompt | ~100 tokens | Instrucciones específicas |
| Response generada | 300-1000 tokens | Email completo con saludo, cuerpo, despedida |
| **TOTAL** | **950-2,200 tokens** | Promedio: **~1,500 tokens** |

**Código relevante:**
```php
// app/Services/VeraResponseGenerator.php:469-482
protected function getEmailContent(Email $email): string
{
    $content = $email->body_text ?? strip_tags($email->body_html ?? '');
    $content = preg_replace('/\n{3,}/', "\n\n", $content);

    // 🎯 OPTIMIZACIÓN: Limitar a 1500 caracteres
    if (strlen($content) > 1500) {
        $content = substr($content, 0, 1500) . '...';
    }

    return trim($content);
}
```

### 3. Chat con Vera (futuro)

| Componente | Tokens | Detalle |
|------------|--------|---------|
| System Prompt | ~100 tokens | Contexto de Vera |
| Pregunta del usuario | 50-200 tokens | Variable |
| Contexto previo | 100-200 tokens | Últimos 3-5 mensajes |
| Respuesta | 100-300 tokens | Respuesta de Vera |
| **TOTAL** | **350-800 tokens** | Promedio: **~500 tokens** |

### 📈 Resumen de consumo

```
┌─────────────────────────┬────────────┬──────────────┬─────────────┐
│ Acción                  │ Min tokens │ Max tokens   │ Promedio    │
├─────────────────────────┼────────────┼──────────────┼─────────────┤
│ Análisis email simple   │    400     │     600      │   ~500      │
│ Análisis email largo    │    600     │     800      │   ~700      │
│ Respuesta corta         │    950     │   1,400      │  ~1,200     │
│ Respuesta larga         │  1,500     │   2,200      │  ~1,800     │
│ Chat interacción        │    350     │     800      │   ~500      │
└─────────────────────────┴────────────┴──────────────┴─────────────┘
```

### 🎯 Promedio ponderado: "1 Email Procesado"

**Escenario típico de uso:**
- 60% de emails solo se analizan (newsletters, notificaciones)
- 40% de emails se analizan + responden

**Cálculo:**
```
Tokens por email = (0.6 × 500) + (0.4 × (500 + 1,500))
                 = 300 + 800
                 = 1,100 tokens/email
```

**Conclusión**: En promedio, procesar 1 email consume **~1,100 tokens**

---

## 💰 Planes y Límites

### Planes disponibles

| Plan | Emails/mes | Tokens equiv. | Precio | Características |
|------|------------|---------------|--------|-----------------|
| **Basic** | 100 emails | ~110,000 tokens | Gratis / €5 | • Análisis automático<br>• Respuestas sugeridas<br>• Reglas básicas<br>• Soporte comunidad |
| **Starter** | 250 emails | ~275,000 tokens | €15/mes | • Todo de Basic<br>• Respuestas automáticas<br>• Análisis semanal<br>• Soporte email (48h) |
| **Pro** | 450 emails | ~495,000 tokens | €30/mes | • Todo de Starter<br>• Reglas ilimitadas<br>• API access<br>• Soporte prioritario (24h)<br>• Analytics avanzados |
| **Enterprise** | Ilimitado | Ilimitado | Custom | • Contactar ventas<br>• On-premise<br>• SLA garantizado |

### Conversión automática Emails ↔ Tokens

```php
// app/Services/VeraUsageService.php

class VeraUsageService
{
    // Promedio de tokens por email procesado
    const TOKENS_PER_EMAIL = 1100;

    public function emailsToTokens(int $emails): int
    {
        return $emails * self::TOKENS_PER_EMAIL;
    }

    public function tokensToEmails(int $tokens): int
    {
        return (int) floor($tokens / self::TOKENS_PER_EMAIL);
    }

    public function getMonthlyTokenLimit(User $user): int
    {
        return $this->emailsToTokens($user->monthly_email_limit);
    }
}
```

---

## 👤 Vista de Usuario (Emails)

**Ruta**: `/settings/usage`

Usuario ve información **simple y comprensible**: cuántos emails ha procesado y cuántos le quedan.

### Diseño de la vista

```
══════════════════════════════════════════════════════════════
                        USO DEL PLAN
══════════════════════════════════════════════════════════════

Plan actual: BASIC (100 emails/mes)                 [Upgrade →]
┌────────────────────────────────────────────────────────────┐
│ Emails procesados este mes                                 │
│ ████████░░░░░░░░░░░░░░░░░░░░  12 / 100 emails (12%)       │
│                                                             │
│ Se restablece el 1 enero, 2025  •  Quedan 88 emails        │
└────────────────────────────────────────────────────────────┘


━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
Desglose de uso (Diciembre 2024)
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

📊 Emails analizados automáticamente
   8 emails procesados

✉️  Respuestas generadas por Vera
   4 respuestas creadas

💬 Interacciones con Vera (chat)
   0 conversaciones

──────────────────────────────────────────────────────────────
📈 RESUMEN TOTAL
   • 12 emails procesados este mes
   • Promedio: 3 emails/semana

   Última actualización: hace 2 minutos ⟳

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
Proyección y recomendaciones
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

✅ Te quedan 88 emails disponibles este mes

📊 Al ritmo actual (~3 emails/semana), alcanzarás el límite
   aproximadamente el 28 de diciembre.

💡 ¿Procesando más emails de lo esperado?
   • Plan Starter: 250 emails/mes por solo €15
   • Plan Pro: 450 emails/mes por €30

   [Ver comparativa de planes]  [Upgrade ahora]

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
Actividad reciente
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

Hoy                     3 emails
Ayer                    2 emails
Esta semana             5 emails
Este mes               12 emails

[Ver historial completo]
```

### Componente de alerta (cuando se acerca al límite)

```
┌────────────────────────────────────────────────────────────┐
│ ⚠️  ALERTA: Has usado el 80% de tu plan                    │
│                                                             │
│ 80 / 100 emails procesados                                 │
│ ████████████████░░░░                                       │
│                                                             │
│ Te quedan 20 emails. Considera hacer upgrade antes de      │
│ alcanzar el límite.                                         │
│                                                             │
│ [Ver planes]  [Recordarme mañana]  [×]                     │
└────────────────────────────────────────────────────────────┘
```

---

## 🔧 Vista Admin (Tokens Analytics)

**Ruta**: `/admin/vera/token-analytics`

**Autenticación**: Solo accesible para `role = 'admin'`

Vista **técnica y detallada** para optimizar consumo de tokens, detectar anomalías y preparar optimizaciones futuras.

### Diseño de la vista admin

```
══════════════════════════════════════════════════════════════
              VERA TOKEN ANALYTICS - ADMIN PANEL
══════════════════════════════════════════════════════════════

📊 RESUMEN GENERAL (Diciembre 2024)
──────────────────────────────────────────────────────────────
Total usuarios activos:        1,245
Total emails procesados:      14,567
Total tokens consumidos:  16,023,700 tokens
Costo total estimado:         €1.20 ($1.60)
Costo promedio/usuario:       €0.001

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
📈 CONSUMO POR ACCIÓN (Este mes)
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

┌──────────────────┬──────────┬────────────────┬──────────┬─────────┐
│ Tipo de acción   │ Cantidad │ Total tokens   │ Promedio │ % Total │
├──────────────────┼──────────┼────────────────┼──────────┼─────────┤
│ Email Analysis   │  8,234   │   4,234,500    │   514    │  26.4%  │
│ Response Gen     │  6,333   │  11,789,200    │  1,862   │  73.6%  │
│ Chat (future)    │    0     │        0       │    0     │   0.0%  │
├──────────────────┼──────────┼────────────────┼──────────┼─────────┤
│ TOTAL            │ 14,567   │  16,023,700    │  1,100   │  100%   │
└──────────────────┴──────────┴────────────────┴──────────┴─────────┘

🎯 Observaciones:
   • Las respuestas generadas consumen 3.6x más tokens que análisis
   • Promedio real: 1,100 tokens/email (según cálculo teórico)
   • ✅ Consumo está dentro de lo esperado

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
🔍 ANÁLISIS DETALLADO: Email Analysis
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

Distribución de tokens:
   Min:     245 tokens  (email muy corto)
   Max:     892 tokens  (email largo con attachments)
   P50:     487 tokens  (mediana)
   P90:     756 tokens  (90% de emails consume menos)
   P99:     845 tokens  (99% de emails consume menos)
   Media:   514 tokens

[Ver histograma de distribución]

Top 10 emails que más tokens consumieron:
┌─────┬──────────────────────────────┬────────┬──────────────────┐
│ ID  │ Subject                      │ Tokens │ From             │
├─────┼──────────────────────────────┼────────┼──────────────────┤
│ 123 │ Propuesta proyecto Q4...     │  892   │ client@corp.com  │
│ 456 │ Re: Consulta técnica det...  │  867   │ tech@startup.io  │
│ 789 │ Update: Meeting notes...     │  834   │ boss@company.com │
│ ... │ ...                          │  ...   │ ...              │
└─────┴──────────────────────────────┴────────┴──────────────────┘

[Ver detalles completos] [Exportar CSV]

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
🔍 ANÁLISIS DETALLADO: Response Generation
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

Distribución de tokens:
   Min:     856 tokens
   Max:   3,245 tokens  ⚠️ OUTLIER (investigar)
   P50:   1,789 tokens
   P90:   2,456 tokens
   P99:   2,890 tokens
   Media: 1,862 tokens

Desglose por componente (promedio):
┌──────────────────────────┬────────┬─────────┐
│ Componente               │ Tokens │ % Total │
├──────────────────────────┼────────┼─────────┤
│ System Prompt            │   312  │  16.8%  │
│ Email original           │   456  │  24.5%  │
│ Thread context           │   234  │  12.6%  │
│ User prompt              │    98  │   5.3%  │
│ Response generada        │   762  │  40.9%  │
├──────────────────────────┼────────┼─────────┤
│ TOTAL                    │ 1,862  │  100%   │
└──────────────────────────┴────────┴─────────┘

🎯 Oportunidades de optimización:
   1. Response generada (40.9%) → Limitar maxOutputTokens a 800
   2. Email original (24.5%) → Reducir límite de 1500 a 1200 chars
   3. System Prompt (16.8%) → Comprimir instrucciones (actual: 312)

   Ahorro potencial: ~350 tokens/respuesta (18.8%)

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
📊 TENDENCIAS (Últimos 30 días)
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

[Gráfico de línea: Tokens consumidos por día]

   3M │                                    ╭─╮
      │                               ╭────╯ ╰─╮
   2M │                          ╭────╯        ╰──╮
      │                     ╭────╯                ╰─╮
   1M │         ╭───────────╯                       ╰──╮
      │    ╭────╯                                      ╰─
   0  └────┴────┴────┴────┴────┴────┴────┴────┴────┴────┴
      1    5    10   15   20   25   30   (días)

   Promedio diario: 534,123 tokens
   Proyección mensual: ~16M tokens

   ✅ Dentro del tier gratuito de Gemini (45M/mes)

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
⚠️  ALERTAS Y ANOMALÍAS
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

🔴 Usuario #456 consumió 45,678 tokens hoy (50x promedio)
   • Acción: Investigar posible loop infinito
   • Email ID: #9876 generó 5 respuestas en 2 minutos
   [Ver detalles]

🟡 Respuesta #1234 consumió 3,245 tokens (1.7x promedio)
   • Subject: "Re: Proyecto complejo con múltiples..."
   • Thread: 12 emails previos (demasiado contexto)
   • Recomendación: Limitar context a últimos 5 emails
   [Ver email]

🟢 Todo OK: 98.5% de operaciones dentro de rangos esperados

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
🔬 EXPERIMENTOS Y A/B TESTING
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

Experimento activo: "Prompt optimizado v2"
   • Grupo A (control): Prompt actual → 514 tokens promedio
   • Grupo B (test): Prompt comprimido → 387 tokens promedio
   • Ahorro: 24.7% 🎉
   • Calidad de respuesta: 4.2/5 (vs 4.3/5 control)

   [Desplegar a producción] [Ver detalles]

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
💰 COSTOS Y PROYECCIONES
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

Tier actual: GRATIS (Gemini 1.5 Flash)
   • Límite diario: 1,500 requests/día
   • Límite mensual: ~45M tokens/mes
   • Uso actual: 16M tokens/mes (35.6% del límite)
   • Estado: ✅ Muy por debajo del límite

Proyección si migramos a tier de pago:
   • Input tokens: 6,409,480 @ $0.075/1M = $0.48
   • Output tokens: 9,614,220 @ $0.30/1M = $2.88
   • TOTAL: $3.36/mes

   Ingresos estimados (1,245 usuarios):
   • Basic (70%): 871 users × €5 = €4,355
   • Starter (20%): 249 users × €15 = €3,735
   • Pro (10%): 125 users × €30 = €3,750
   • TOTAL: €11,840/mes

   Margen: 99.97% 💰

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
🛠️  HERRAMIENTAS
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

[Exportar datos (CSV)]
[Ver logs detallados]
[Configurar alertas]
[Ejecutar query personalizado]
[Comparar períodos]
```

### Endpoints para vista admin

```php
// routes/web.php (admin only)

Route::middleware(['auth', 'admin'])->prefix('admin/vera')->group(function () {
    Route::get('/token-analytics', [AdminVeraController::class, 'tokenAnalytics']);
    Route::get('/token-analytics/export', [AdminVeraController::class, 'exportTokenData']);
    Route::get('/token-analytics/user/{user}', [AdminVeraController::class, 'userTokenDetails']);
    Route::get('/token-analytics/email/{email}', [AdminVeraController::class, 'emailTokenDetails']);
    Route::get('/token-analytics/anomalies', [AdminVeraController::class, 'detectAnomalies']);
});
```

---

## 📏 Métrica Clave: "Email Procesado"

### Definición

Un **"email procesado"** es la unidad básica de consumo del usuario.

### Reglas de conteo

| Escenario | ¿Cuenta como email procesado? | Tokens consumidos |
|-----------|-------------------------------|-------------------|
| Email analizado con IA | ✅ SÍ (1 email) | ~500 tokens |
| Email analizado con reglas | ❌ NO (gratis) | 0 tokens |
| Email analizado + respuesta generada | ✅ SÍ (1 email)* | ~2,000 tokens |
| Solo generar respuesta (email ya analizado este mes) | ❌ NO** | ~1,500 tokens |
| Solo generar respuesta (email analizado hace 2 meses) | ✅ SÍ (1 email) | ~2,000 tokens |
| Chat con Vera | ✅ SÍ (0.5 emails) | ~500 tokens |
| Re-analizar mismo email | ❌ NO (cacheable) | 0 tokens |

\* *Se cuenta como 1 solo email, pero consume tokens de análisis + respuesta*
\** *No suma al contador porque ya se contó al analizar*

### Implementación en código

```php
// app/Services/VeraUsageService.php

public function recordEmailProcessing(
    User $user,
    Email $email,
    string $actionType,
    int $tokens
): void {
    // 1. Registrar uso de tokens
    VeraTokenUsage::create([
        'user_id' => $user->id,
        'email_id' => $email->id,
        'action_type' => $actionType,
        'prompt_tokens' => $tokens['prompt'] ?? 0,
        'completion_tokens' => $tokens['completion'] ?? 0,
        'total_tokens' => $tokens['total'],
        'model' => $tokens['model'] ?? 'gemini-1.5-flash',
        'cost' => $this->calculateCost($tokens['total']),
        'metadata' => [
            'subject' => $email->subject,
            'from' => $email->from_email,
            'content_length' => strlen($email->body_text ?? ''),
        ],
    ]);

    // 2. ¿Cuenta como "email procesado" para el límite?
    if ($this->shouldCountAsProcessedEmail($user, $email, $actionType)) {
        // Marcar email como procesado este mes
        $email->update([
            'processed_this_billing_cycle' => true,
            'last_processed_at' => now(),
        ]);

        Log::info('Email marcado como procesado', [
            'user_id' => $user->id,
            'email_id' => $email->id,
            'action' => $actionType,
        ]);
    }
}

protected function shouldCountAsProcessedEmail(
    User $user,
    Email $email,
    string $actionType
): bool {
    // No contar si ya fue procesado este ciclo de facturación
    if ($email->processed_this_billing_cycle) {
        return false;
    }

    // Contar análisis de email como procesado
    if ($actionType === 'email_analysis') {
        return true;
    }

    // Generación de respuesta: solo contar si el email NO fue analizado este ciclo
    if ($actionType === 'response_generation') {
        return !$email->processed_this_billing_cycle;
    }

    // Chat: contar como 0.5 (implementación futura)
    if ($actionType === 'chat_interaction') {
        return true; // Lógica especial para chat
    }

    return false;
}
```

---

## 🚨 Sistema de Alertas

### Niveles de alerta

| Uso | Nivel | Color | Acción |
|-----|-------|-------|--------|
| 0-49% | ✅ Normal | Verde | Sin alertas |
| 50-74% | ℹ️ Informativo | Azul | Notificación suave en dashboard |
| 75-89% | ⚠️ Advertencia | Naranja | Alert banner + email |
| 90-99% | 🔴 Crítico | Rojo | Alert modal + email urgente |
| 100%+ | 🚫 Bloqueado | Rojo | Bloquear IA + email + CTA upgrade |

### Ejemplos de alertas

**50% - Informativo**
```
┌────────────────────────────────────────────────────────────┐
│ ℹ️  Has usado la mitad de tu plan mensual                  │
│                                                             │
│ 50 / 100 emails procesados                                 │
│                                                             │
│ Todavía te quedan 50 emails disponibles.                   │
│ [×]                                                         │
└────────────────────────────────────────────────────────────┘
```

**75% - Advertencia**
```
┌────────────────────────────────────────────────────────────┐
│ ⚠️  ADVERTENCIA: Quedan pocos emails en tu plan            │
│                                                             │
│ 75 / 100 emails procesados  ████████████████░░░░           │
│                                                             │
│ Solo te quedan 25 emails. Si continúas al ritmo actual,    │
│ alcanzarás el límite en ~5 días.                           │
│                                                             │
│ [Ver planes]  [Upgrade ahora]  [×]                         │
└────────────────────────────────────────────────────────────┘
```

**90% - Crítico (Modal)**
```
┏━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━┓
┃  🔴 CRÍTICO: Solo quedan 10 emails en tu plan           ┃
┗━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━┛

     90 / 100 emails procesados  ██████████████████░░

Has consumido el 90% de tu plan mensual. Te quedan solo
10 emails antes de alcanzar el límite.

¿Qué sucede cuando alcances el límite?
   • Vera dejará de analizar emails automáticamente con IA
   • Seguirás recibiendo emails normalmente
   • Las reglas automáticas seguirán funcionando
   • Podrás hacer upgrade en cualquier momento

Recomendaciones:
   ✓ Plan Starter: 250 emails/mes por €15 (ahorra vs €5)
   ✓ Plan Pro: 450 emails/mes por €30 (mejor precio)

              [Upgrade a Starter]  [Upgrade a Pro]

                     [Recordarme mañana]
```

**100% - Bloqueado**
```
┏━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━┓
┃  🚫 Has alcanzado el límite de tu plan                  ┃
┗━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━┛

     100 / 100 emails procesados  ████████████████████

Has consumido todos los emails de tu plan Basic.

Vera ha pausado el análisis automático con IA hasta el
próximo ciclo de facturación (1 enero, 2025).

Mientras tanto:
   ✓ Seguirás recibiendo emails normalmente
   ✓ Las reglas automáticas siguen activas
   ✓ Puedes ver emails y responder manualmente

¿Necesitas más emails ahora?
Haz upgrade y desbloquea Vera inmediatamente:

              [Upgrade a Starter - €15]
              [Upgrade a Pro - €30]

              Próximo reset: en 5 días
```

### Notificaciones por email

```php
// app/Notifications/VeraUsageAlertNotification.php

class VeraUsageAlertNotification extends Notification
{
    public function __construct(
        protected int $percentage,
        protected int $used,
        protected int $limit,
        protected int $remaining
    ) {}

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        $level = $this->getAlertLevel();

        return (new MailMessage)
            ->subject("Vera: Has usado el {$this->percentage}% de tu plan")
            ->line($this->getMessage())
            ->action('Ver uso detallado', route('settings.usage'))
            ->when($level === 'critical', function ($message) {
                $message->action('Hacer upgrade', route('settings.plans'));
            });
    }

    protected function getMessage(): string
    {
        return match($this->getAlertLevel()) {
            'info' => "Has procesado {$this->used} de {$this->limit} emails este mes.",
            'warning' => "Solo te quedan {$this->remaining} emails disponibles este mes.",
            'critical' => "¡Atención! Solo te quedan {$this->remaining} emails. Considera hacer upgrade.",
            'blocked' => "Has alcanzado el límite de tu plan. Vera está pausada hasta el próximo ciclo.",
        };
    }

    protected function getAlertLevel(): string
    {
        return match(true) {
            $this->percentage >= 100 => 'blocked',
            $this->percentage >= 90 => 'critical',
            $this->percentage >= 75 => 'warning',
            default => 'info'
        };
    }
}
```

---

## 🛠️ Implementación Paso a Paso

### Fase 1: Base de datos y registro (Semana 1)

**Objetivo**: Empezar a registrar todos los usos de tokens

- [x] **1.1** Crear migración `create_vera_token_usage_table`
  ```bash
  php artisan make:migration create_vera_token_usage_table
  ```

- [ ] **1.2** Crear modelo `VeraTokenUsage` con:
  - Relationships: `belongsTo(User)`, `belongsTo(Email)`
  - Scopes: `thisMonth()`, `byActionType()`, `byUser()`
  - Accessors: `getCostAttribute()`

- [ ] **1.3** Migración para añadir campos a `users`:
  ```sql
  ALTER TABLE users ADD plan_type ENUM(...);
  ALTER TABLE users ADD monthly_email_limit INT;
  ALTER TABLE users ADD billing_cycle_start DATE;
  ```

- [ ] **1.4** Migración para añadir campo a `emails`:
  ```sql
  ALTER TABLE emails ADD processed_this_billing_cycle BOOLEAN DEFAULT FALSE;
  ALTER TABLE emails ADD last_processed_at TIMESTAMP NULL;
  ```

- [ ] **1.5** Modificar `GeminiProvider::generateContent()` para registrar automáticamente:
  ```php
  // Después de recibir respuesta de Gemini
  event(new VeraTokensConsumed([
      'user_id' => $userId,
      'action_type' => $actionType,
      'email_id' => $emailId,
      'tokens' => $response['usage'],
  ]));
  ```

- [ ] **1.6** Crear listener `RecordTokenUsage` que escucha `VeraTokensConsumed`

- [ ] **1.7** Seeders para planes:
  ```php
  User::where('id', 1)->update(['plan_type' => 'basic', 'monthly_email_limit' => 100]);
  ```

### Fase 2: Lógica de negocio (Semana 1-2)

**Objetivo**: Crear el servicio que calcula y gestiona el uso

- [ ] **2.1** Crear `VeraUsageService` con métodos:
  ```php
  calculateMonthlyUsage(User $user): array
  getEmailsProcessedThisMonth(User $user): int
  getRemainingEmails(User $user): int
  getTokensByActionType(User $user, $month): array
  canProcessEmail(User $user): bool
  recordEmailProcessing(User $user, Email $email, $tokens, $action): void
  getUsagePercentage(User $user): float
  projectLimitReachDate(User $user): ?Carbon
  ```

- [ ] **2.2** Integrar `VeraUsageService` en:
  - `VeraService::analyzeEmail()` → Verificar límite antes de analizar
  - `VeraResponseGenerator::generateResponse()` → Verificar límite antes de generar
  - `ProcessEmailWithVera` job → Registrar uso después de procesar

- [ ] **2.3** Crear comando Artisan `vera:reset-monthly-limits`:
  ```bash
  php artisan vera:reset-monthly-limits
  ```
  - Resetea `processed_this_billing_cycle = false` para todos los emails
  - Ejecutar mensualmente con cron

### Fase 3: Control de límites (Semana 2)

**Objetivo**: Bloquear uso cuando se excede límite

- [ ] **3.1** Crear middleware `VeraEmailLimitMiddleware`:
  ```php
  public function handle($request, Closure $next)
  {
      $user = $request->user();

      if (!$this->usageService->canProcessEmail($user)) {
          return response()->json([
              'error' => 'Has alcanzado el límite de tu plan',
              'limit' => $user->monthly_email_limit,
              'upgrade_url' => route('settings.plans'),
          ], 429); // Too Many Requests
      }

      return $next($request);
  }
  ```

- [ ] **3.2** Aplicar middleware en rutas críticas:
  ```php
  Route::post('/vera/analyze-email', ...)->middleware('vera.limit');
  Route::post('/vera/generate-response', ...)->middleware('vera.limit');
  ```

- [ ] **3.3** Modificar `VeraService::analyzeEmail()` para manejar límites:
  ```php
  if (!$this->usageService->canProcessEmail($user)) {
      Log::warning('Usuario alcanzó límite, usando solo reglas', [
          'user_id' => $user->id,
      ]);

      // Usar solo reglas (gratis)
      $analysis = $this->rulesService->analyzeEmail($email);

      // Marcar que se saltó IA por límite
      $email->update(['vera_ai_skipped' => true]);

      return $analysis;
  }
  ```

- [ ] **3.4** Crear observer `VeraUsageObserver` que detecta:
  - Cuando usuario alcanza 50%, 75%, 90%, 100%
  - Dispara notificaciones automáticamente

### Fase 4: Vista e interfaz (Semana 2-3)

**Objetivo**: Mostrar uso al usuario

- [ ] **4.1** Crear controlador `VeraUsageController`:
  ```php
  public function index()
  {
      $user = Auth::user();
      $usage = $this->usageService->calculateMonthlyUsage($user);

      return view('settings.usage', compact('usage'));
  }
  ```

- [ ] **4.2** Crear vista `resources/views/settings/usage.blade.php`:
  - Barra de progreso
  - Desglose por acción
  - Proyección
  - CTA de upgrade

- [ ] **4.3** Crear componente Livewire `UsageAlert` para dashboard:
  ```php
  class UsageAlert extends Component
  {
      public function render()
      {
          $percentage = $this->usageService->getUsagePercentage(auth()->user());

          if ($percentage < 75) {
              return null; // No mostrar alerta
          }

          return view('livewire.usage-alert', compact('percentage'));
      }
  }
  ```

- [ ] **4.4** Crear componente `UsageWidget` para sidebar:
  ```blade
  <div class="usage-widget">
      <div class="progress-bar">
          <div style="width: {{ $percentage }}%"></div>
      </div>
      <p>{{ $used }} / {{ $limit }} emails</p>
  </div>
  ```

### Fase 5: Vista Admin (Semana 3)

**Objetivo**: Panel de analytics para optimización

- [ ] **5.1** Crear `AdminVeraController`:
  ```php
  public function tokenAnalytics()
  {
      $analytics = [
          'total_users' => User::count(),
          'total_emails_processed' => VeraTokenUsage::thisMonth()->count(),
          'total_tokens' => VeraTokenUsage::thisMonth()->sum('total_tokens'),
          'by_action' => VeraTokenUsage::thisMonth()
              ->selectRaw('action_type, COUNT(*) as count, SUM(total_tokens) as tokens')
              ->groupBy('action_type')
              ->get(),
          'distribution' => $this->getTokenDistribution(),
          'anomalies' => $this->detectAnomalies(),
      ];

      return view('admin.vera.token-analytics', compact('analytics'));
  }
  ```

- [ ] **5.2** Crear vista `resources/views/admin/vera/token-analytics.blade.php`
  - Gráficos con Chart.js
  - Tablas de datos
  - Exportación CSV
  - Alerts de anomalías

- [ ] **5.3** Implementar detección de anomalías:
  ```php
  protected function detectAnomalies(): Collection
  {
      // Usuarios que consumen 3x más tokens que promedio
      $avgTokens = VeraTokenUsage::thisMonth()->avg('total_tokens');

      return VeraTokenUsage::thisMonth()
          ->selectRaw('user_id, SUM(total_tokens) as total')
          ->groupBy('user_id')
          ->having('total', '>', $avgTokens * 3)
          ->get();
  }
  ```

- [ ] **5.4** Crear endpoint de exportación:
  ```php
  public function exportTokenData(Request $request)
  {
      $data = VeraTokenUsage::query()
          ->when($request->from, fn($q) => $q->where('created_at', '>=', $request->from))
          ->when($request->to, fn($q) => $q->where('created_at', '<=', $request->to))
          ->get();

      return Excel::download(new TokenUsageExport($data), 'vera-tokens.csv');
  }
  ```

### Fase 6: Gestión de planes (Semana 3-4)

**Objetivo**: Sistema de upgrade/downgrade

- [ ] **6.1** Crear controlador `PlanController`:
  ```php
  public function upgrade(Request $request)
  {
      $user = Auth::user();
      $newPlan = $request->input('plan'); // 'starter', 'pro'

      // Validar plan
      // Procesar pago (Stripe/PayPal)
      // Actualizar usuario

      $user->update([
          'plan_type' => $newPlan,
          'monthly_email_limit' => $this->getPlanLimit($newPlan),
          'plan_upgraded_at' => now(),
      ]);

      // Resetear contador inmediatamente al hacer upgrade
      $this->usageService->resetBillingCycle($user);

      return redirect()->route('settings.usage')
          ->with('success', '¡Plan actualizado! Límites reseteados.');
  }
  ```

- [ ] **6.2** Vista de comparativa de planes:
  ```blade
  <div class="plans-grid">
      @foreach($plans as $plan)
          <div class="plan-card {{ $plan->name === $currentPlan ? 'current' : '' }}">
              <h3>{{ $plan->name }}</h3>
              <p class="price">€{{ $plan->price }}/mes</p>
              <ul>
                  <li>{{ $plan->email_limit }} emails/mes</li>
                  <li>{{ $plan->features }}</li>
              </ul>
              <button>{{ $plan->cta }}</button>
          </div>
      @endforeach
  </div>
  ```

- [ ] **6.3** Comando cron `vera:reset-monthly-limits`:
  ```php
  protected function handle()
  {
      $users = User::whereRaw('DAY(billing_cycle_start) = DAY(NOW())')->get();

      foreach ($users as $user) {
          // Resetear flag de emails procesados
          Email::where('user_id', $user->id)
              ->update(['processed_this_billing_cycle' => false]);

          $this->info("Reset billing cycle for user {$user->id}");
      }
  }
  ```

  Añadir a `app/Console/Kernel.php`:
  ```php
  protected function schedule(Schedule $schedule)
  {
      $schedule->command('vera:reset-monthly-limits')->daily();
  }
  ```

- [ ] **6.4** Configurar webhooks de pago (Stripe):
  ```php
  Route::post('/webhooks/stripe', [WebhookController::class, 'handleStripe']);
  ```

---

## ⚖️ Casos Edge y Reglas de Negocio

### 1. Usuario excede el límite a mitad de mes

**Comportamiento**:
- Vera deja de usar IA automáticamente
- Sigue usando reglas (gratis)
- Emails marcados con `vera_ai_skipped = true`
- Usuario puede hacer upgrade en cualquier momento
- Al hacer upgrade, límites se resetean inmediatamente

**Código**:
```php
// app/Services/VeraService.php
if (!$this->usageService->canProcessEmail($user)) {
    Log::info('Usuario sin crédito, usando solo reglas', [
        'user_id' => $user->id,
        'emails_used' => $this->usageService->getEmailsProcessedThisMonth($user),
        'limit' => $user->monthly_email_limit,
    ]);

    // Fallback a reglas
    return $this->rulesService->analyzeEmail($email);
}
```

### 2. Usuario hace downgrade

**Comportamiento**:
- El cambio se aplica al inicio del próximo ciclo de facturación
- No pierde funcionalidad durante el mes actual
- Se le notifica la fecha exacta del cambio

**Código**:
```php
$user->update([
    'plan_type_next_cycle' => 'basic', // Cambio pendiente
    'plan_downgrade_effective_date' => $user->billing_cycle_start->addMonth(),
]);
```

### 3. ¿Se acumulan emails no usados?

**Respuesta**: NO

- Los límites se resetean cada mes
- No hay rollover de emails no usados
- Política estándar de SaaS

### 4. Emails en cola cuando se excede límite

**Comportamiento**:
- Emails ya en cola se procesan normalmente (no cancelar trabajos)
- Nuevos emails no entran a cola con IA
- Se procesan con reglas

**Código**:
```php
// app/Jobs/ProcessEmailWithVera.php
public function handle()
{
    // Los jobs ya dispatched se ejecutan incluso si usuario alcanzó límite
    // Esto evita romper la experiencia del usuario

    try {
        $this->veraService->analyzeEmail($this->email);
    } catch (UsageLimitExceededException $e) {
        // Fallback a reglas
        $this->rulesEngine->applyRules($this->email);
    }
}
```

### 5. Usuario con múltiples cuentas de email

**Comportamiento**:
- El límite es por USUARIO, no por cuenta de email
- Si tiene Gmail + Outlook, ambos comparten los 100 emails/mes

### 6. Re-analizar el mismo email

**Comportamiento**:
- Si el usuario pide re-analizar un email ya analizado este mes: NO consume
- Usamos caché del análisis previo
- Se guarda en `emails.vera_summary`, etc.

**Código**:
```php
if ($email->vera_processed && $email->last_processed_at->isSameMonth(now())) {
    Log::info('Email ya analizado este mes, usando caché');
    return [
        'summary' => $email->vera_summary,
        'sentiment_score' => $email->vera_sentiment_score,
        // ... cached data
        'from_cache' => true,
    ];
}
```

---

## 💰 Estimación de Costos

### Costos de Gemini 1.5 Flash

**Tier gratuito:**
- 1,500 requests/día
- ~45M tokens/mes incluidos
- Suficiente para ~40,000 emails procesados/mes
- **Costo**: €0

**Tier de pago (si superas gratuito):**
- Input: $0.075 por 1M tokens
- Output: $0.30 por 1M tokens
- Promedio ponderado: ~$0.10 por 1M tokens

### Cálculo por plan

| Plan | Emails/mes | Tokens/mes | Input | Output | Costo total |
|------|------------|------------|-------|--------|-------------|
| Basic | 100 | 110,000 | $0.008 | $0.033 | **$0.04** |
| Starter | 250 | 275,000 | $0.021 | $0.083 | **$0.10** |
| Pro | 450 | 495,000 | $0.037 | $0.149 | **$0.19** |

### Rentabilidad

| Plan | Precio venta | Costo Gemini | Margen bruto | % Margen |
|------|-------------|--------------|--------------|----------|
| Basic | €5 | €0.04 | €4.96 | **99.2%** |
| Starter | €15 | €0.10 | €14.90 | **99.3%** |
| Pro | €30 | €0.19 | €29.81 | **99.4%** |

**Conclusión**: Margen brutal incluso si los usuarios consumen el doble.

### Proyección con 1,000 usuarios

Distribución típica SaaS:
- 70% Basic (700 users) → 700 × €5 = €3,500
- 20% Starter (200 users) → 200 × €15 = €3,000
- 10% Pro (100 users) → 100 × €30 = €3,000

**Ingresos totales**: €9,500/mes

**Costos Gemini** (asumiendo uso promedio):
- Basic: 700 × €0.04 = €28
- Starter: 200 × €0.10 = €20
- Pro: 100 × €0.19 = €19
- **Total**: €67/mes

**Beneficio neto**: €9,433/mes (99.3% de margen)

---

## 🚀 Optimización Futura

### Objetivos de optimización

1. **Reducir prompts sin perder calidad**
2. **Detectar emails que no necesitan IA**
3. **Implementar caché inteligente**
4. **A/B testing de prompts**
5. **Migrar a Python para reglas complejas**

### 1. Comprimir System Prompts

**Actual** (312 tokens):
```
Eres Vera, tu asistente virtual de email inteligente...
[Larga descripción de personalidad e instrucciones]
```

**Optimizado** (187 tokens, -40%):
```
Eres Vera, asistente de email. Analiza y devuelve JSON:
{"summary":"...", "sentiment_score":0.5, "priority":"normal"}
```

### 2. Limitar contenido de emails

**Actual**:
- Análisis: 2000 chars max
- Respuestas: 1500 chars max

**Optimización**:
- Análisis: 1200 chars (-40%)
- Respuestas: 1000 chars body + 300 chars context (-33%)

**Ahorro esperado**: ~200 tokens/email

### 3. Caché de análisis

```php
// app/Services/VeraService.php

protected function getFromCacheOrAnalyze(Email $email): array
{
    $cacheKey = "vera:email:{$email->id}:analysis";

    return Cache::remember($cacheKey, now()->addDays(30), function() use ($email) {
        return $this->analyzeWithAI($email);
    });
}
```

### 4. Reglas + IA híbrido inteligente

**Estrategia**: Usar IA solo cuando reglas no tienen suficiente confianza

```php
// app/Services/VeraService.php

public function analyzeEmail(Email $email): array
{
    $rulesAnalysis = $this->rulesService->analyzeEmail($email);

    // Si las reglas tienen alta confianza (>0.85), no usar IA
    if ($rulesAnalysis['confidence'] > 0.85) {
        Log::info('Alta confianza en reglas, saltando IA', [
            'email_id' => $email->id,
            'confidence' => $rulesAnalysis['confidence'],
            'tokens_saved' => 500,
        ]);

        return $rulesAnalysis;
    }

    // Solo usar IA si necesario
    return $this->analyzeWithAI($email);
}
```

**Ahorro esperado**: 30-50% de llamadas a IA

### 5. Detección de newsletters automática (sin IA)

```php
protected function isNewsletterWithoutAI(Email $email): bool
{
    $indicators = [
        'unsubscribe' => str_contains($email->body_text, 'unsubscribe'),
        'noreply' => str_contains($email->from_email, 'noreply'),
        'list_unsubscribe' => !empty($email->headers['List-Unsubscribe']),
        'bulk' => str_contains($email->headers['Precedence'] ?? '', 'bulk'),
    ];

    // Si 2+ indicadores → es newsletter
    return count(array_filter($indicators)) >= 2;
}
```

### 6. Python para reglas complejas

**Plan futuro**:
- Crear microservicio Python con FastAPI
- Usar reglas basadas en ML (sklearn, spaCy)
- Laravel llama a Python solo para emails complejos

```python
# python-vera-service/main.py
from fastapi import FastAPI
from transformers import pipeline

app = FastAPI()
sentiment_analyzer = pipeline("sentiment-analysis")

@app.post("/analyze")
def analyze_email(email: dict):
    # Análisis con ML sin Gemini
    sentiment = sentiment_analyzer(email["content"])[0]

    return {
        "summary": extract_summary(email["content"]),
        "sentiment_score": sentiment["score"],
        "sentiment_label": sentiment["label"],
        "priority": classify_priority(email),
        "tokens_used": 0,  # Sin costo!
    }
```

**Ventajas**:
- Análisis gratis (sin tokens)
- Más rápido que llamadas a API
- Mayor control

### 7. A/B Testing de prompts

```php
// app/Services/Experiments/PromptExperiment.php

class PromptExperiment
{
    public function getPromptVariant(User $user): string
    {
        $variant = $user->id % 2 === 0 ? 'control' : 'optimized';

        return $variant === 'control'
            ? $this->getControlPrompt()
            : $this->getOptimizedPrompt();
    }

    public function trackResult(string $variant, int $tokens, float $quality)
    {
        ExperimentResult::create([
            'variant' => $variant,
            'tokens_used' => $tokens,
            'quality_score' => $quality,
        ]);
    }
}
```

### Tabla de ahorro proyectado

| Optimización | Ahorro por email | Ahorro mensual (100 emails) |
|--------------|------------------|----------------------------|
| Comprimir prompts | -125 tokens | -12,500 tokens |
| Limitar contenido | -200 tokens | -20,000 tokens |
| Caché inteligente | -30% llamadas | -33,000 tokens |
| Reglas híbridas | -40% llamadas | -44,000 tokens |
| **TOTAL** | **-550 tokens/email** | **-55,000 tokens** |

**De 1,100 tokens/email → 550 tokens/email = 50% de ahorro** 🎉

---

## 📝 Resumen Ejecutivo

### Objetivos cumplidos

✅ **Control total de consumo de tokens**
✅ **Vista simple para usuarios (emails procesados)**
✅ **Vista técnica para admin (tokens detallados)**
✅ **Sistema de planes escalable**
✅ **Alertas automáticas**
✅ **Rentabilidad del 99%+**
✅ **Preparado para optimización futura**

### Métricas clave

- **Promedio de tokens por email**: 1,100 tokens
- **Costo por email**: €0.0004
- **Margen de beneficio**: 99.3%
- **Capacidad sin costo**: ~40,000 emails/mes (tier gratis Gemini)

### Estado de Implementación

1. ✅ **Fase 1: Base de datos y registro** - COMPLETADA (27 dic 2024)
   - Tabla vera_token_usage
   - Modelo VeraTokenUsage
   - Event VeraTokensConsumed + Listener RecordTokenUsage
   - Integración con GeminiProvider
   - Migracione de users y emails

2. ✅ **Fase 2: Lógica de negocio** - COMPLETADA (27 dic 2024)
   - VeraUsageService con todos los métodos
   - Integración con VeraService y VeraResponseGenerator
   - Comando vera:reset-monthly-limits
   - Sistema de control de límites
   - Fallback a reglas cuando se excede límite

3. ✅ **Fase 3: Control de límites (Middleware + UI)** - COMPLETADA (27 dic 2024)
   - Middleware VeraEmailLimitMiddleware
   - Controlador VeraUsageController
   - Vistas /settings/usage y /settings/plans
   - Sistema de alertas visuales (5 niveles)
   - Cambio de plan vía AJAX
   - Rutas protegidas con middleware

4. ✅ **Fase 4: Vista Admin (Analytics)** - COMPLETADA (27 dic 2024)
   - Controlador Admin\VeraAnalyticsController
   - Vista admin/vera/analytics
   - Top usuarios por consumo
   - Consumo por tipo de acción
   - Últimos 30 días de datos
   - Exportación a CSV
   - Ruta /admin/vera/analytics

5. ⏳ **Fase 5: Notificaciones** - PENDIENTE
   - Emails automáticos al alcanzar 75%, 90%, 100%
   - Notificaciones in-app
   - Sistema de alertas proactivo

6. ⏳ **Fase 6: Integración de pagos** - PENDIENTE
   - Stripe/PayPal checkout
   - Webhooks de pago
   - Gestión de suscripciones recurrentes
   - Facturas automáticas

7. 🔮 **Optimizaciones futuras** - ROADMAP
   - Python microservice para ML
   - A/B testing de prompts
   - Caché inteligente
   - Reglas híbridas (IA + ML local)

---

## 📁 Archivos Creados

### Fase 1 (7 archivos)
- `database/migrations/2025_12_27_200000_create_vera_token_usage_table.php`
- `database/migrations/2025_12_27_200100_add_plan_fields_to_users_table.php`
- `database/migrations/2025_12_27_200200_add_processing_fields_to_emails_table.php`
- `app/Models/VeraTokenUsage.php`
- `app/Events/VeraTokensConsumed.php`
- `app/Listeners/RecordTokenUsage.php`
- `database/seeders/UserPlansSeeder.php`

### Fase 2 (3 archivos)
- `app/Services/VeraUsageService.php`
- `app/Exceptions/UsageLimitExceededException.php`
- `app/Console/Commands/VeraResetMonthlyLimits.php`

### Fase 3 (4 archivos)
- `app/Http/Middleware/VeraEmailLimitMiddleware.php`
- `app/Http/Controllers/VeraUsageController.php`
- `resources/views/settings/usage.blade.php`
- `resources/views/settings/plans.blade.php`

### Fase 4 (2 archivos)
- `app/Http/Controllers/Admin/VeraAnalyticsController.php`
- `resources/views/admin/vera/analytics.blade.php`

### Documentación (4 archivos)
- `gestion-planes-y-tokens.md` (Master)
- `FASE-1-IMPLEMENTACION.md`
- `FASE-2-IMPLEMENTACION.md`
- `FASE-3-IMPLEMENTACION.md`

### Testing (2 archivos)
- `app/Console/Commands/TestTokenRegistration.php`
- `app/Console/Commands/TestVeraUsageSystem.php`

**Total**: 22 archivos creados
**Total**: 5 archivos modificados

---

**Fecha de creación**: 27 diciembre 2024
**Última actualización**: 27 diciembre 2024
**Versión**: 2.0
**Autor**: Sistema Vera
**Estado**: ✅ CORE COMPLETADO (Fases 1-4)
**Próximo**: Notificaciones y Pagos (Fases 5-6)
