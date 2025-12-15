# 🤖 ESTRATEGIA DE IA Y APIS PARA VERA
## Análisis, Decisiones y Roadmap Técnico

**Fecha creación**: 14 Diciembre 2025
**Estado**: ✅ ESTRATEGIA DEFINIDA
**Versión**: 1.0

---

## 📋 ÍNDICE

1. [Contexto y Descubrimiento](#contexto-y-descubrimiento)
2. [Análisis de Opciones](#análisis-de-opciones)
3. [Decisión Final](#decisión-final)
4. [Plan de Implementación por Fases](#plan-de-implementación-por-fases)
5. [Costes Detallados](#costes-detallados)
6. [Arquitectura Técnica](#arquitectura-técnica)
7. [Migración entre Fases](#migración-entre-fases)
8. [Referencias y Recursos](#referencias-y-recursos)

---

## 🔍 CONTEXTO Y DESCUBRIMIENTO

### Descubrimiento clave

Investigando cómo funcionan los SaaS exitosos con IA, descubrimos que:

- **Mayoría desarrollan IA local** (Ollama, modelos propios)
- **Destinan solo 1-5% a APIs públicas** (casos complejos)
- **PERO**: No empezaron así desde el día 1

### La realidad que no cuentan

Los SaaS exitosos siguieron este patrón:

```
FASE 1 (Mes 0-3): APIs públicas 100%
  ↓ (Validar producto, conseguir usuarios)

FASE 2 (Mes 4-6): APIs públicas optimizadas
  ↓ (Reducir costes, mejorar márgenes)

FASE 3 (Mes 7-12): Híbrido (Local 70% + APIs 30%)
  ↓ (Solo cuando costes APIs > €50/mes)

FASE 4 (Año 2+): IA local + APIs para casos edge
  ✓ (Miles de usuarios, flujo positivo)
```

### ¿Por qué este orden?

| Razón | Explicación |
|-------|-------------|
| **Validación rápida** | APIs permiten MVP en días, no meses |
| **Cero infraestructura** | No necesitas GPUs ni servidores potentes |
| **Mejor calidad inicial** | GPT-4/Claude > Llama 3 8B locales |
| **Enfoque en producto** | Tiempo en features, no en configurar IA |
| **Escalamiento gradual** | Migras a local cuando economía lo justifica |

---

## 🎯 ANÁLISIS DE OPCIONES

### OPCIÓN 1: APIs Públicas (OpenAI/Claude/Gemini)

#### Proveedores analizados

| Proveedor | Modelo | Input (1M tokens) | Output (1M tokens) | Coste/email |
|-----------|--------|-------------------|-------------------|-------------|
| **OpenAI** | GPT-4 Turbo | $10 | $30 | ~€0.003 |
| **OpenAI** | GPT-3.5 Turbo | $0.50 | $1.50 | ~€0.0002 |
| **Anthropic** | Claude 3.5 Sonnet | $3 | $15 | ~€0.0008 |
| **Anthropic** | Claude 3 Haiku | $0.25 | $1.25 | ~€0.0001 |
| **Google** | Gemini 1.5 Pro | $1.25 | $5 | ~€0.0005 |
| **Google** | Gemini 1.5 Flash | **GRATIS** | **GRATIS** | **€0** |

#### ✅ PROS

- Cero infraestructura (no necesitas GPUs)
- Arranque inmediato (copias API key → funciona)
- Mejor calidad (GPT-4, Claude 3.5 superiores a modelos locales pequeños)
- Sin mantenimiento (ellos actualizan, escalan, etc.)
- Desarrollo rápido (te enfocas en producto)

#### ❌ CONTRAS

- Costes variables (cada request cobra)
- Dependencia externa (si caen APIs, afecta tu app)
- Privacidad (emails van a servidores externos - mitigable con anonimización)
- Rate limits (aunque generosos para inicio)

#### 💰 Costes proyectados

**Escenario 1 usuario activo (500 emails/mes):**
- Con **Gemini Flash**: €0/mes (gratis hasta 1,500 requests/día)
- Con **Claude 3.5**: €0.40/mes
- Con **GPT-4**: €1.40/mes

**Escenario 50 usuarios activos (25,000 emails/mes):**
- Con **Gemini Flash**: €0/mes (aún bajo límite gratis)
- Con **Claude 3.5** (40% emails): €8/mes
- Con **GPT-4**: €70/mes

**Escenario 500 usuarios activos (250,000 emails/mes):**
- Con **Claude 3.5** (50% emails): €100/mes
- Ingreso esperado: €5,000/mes (€10/usuario)
- **Margen: 98%**

---

### OPCIÓN 2: IA Local (Ollama/Llama)

#### Modelos locales disponibles

| Modelo | VRAM necesaria | Calidad (1-10) | Velocidad | Casos de uso |
|--------|---------------|----------------|-----------|--------------|
| **Llama 3 8B** | 8GB | 7/10 | Rápido | Análisis estándar |
| **Llama 3 70B** | 40GB+ | 8.5/10 | Lento | Casos complejos |
| **Mistral 7B** | 8GB | 6.5/10 | Muy rápido | Categorización |
| **Mixtral 8x7B** | 16GB | 8/10 | Medio | Análisis avanzado |

#### Comparativa calidad vs APIs

```
GPT-4 Turbo:        ████████████ 10/10
Claude 3.5 Sonnet:  ███████████░  9.5/10
Gemini 1.5 Pro:     ███████████░  9/10
Llama 3 70B:        █████████░░░  8.5/10
Llama 3 8B:         ███████░░░░░  7/10  ← Trade-off crítico
Mistral 7B:         ██████░░░░░░  6.5/10
```

#### ✅ PROS

- Cero costes por request (una vez instalado, gratis)
- Privacidad total (todo queda en tu servidor)
- Control absoluto (puedes ajustar/afinar modelos)
- Sin límites de rate (procesas lo que GPU aguante)
- Sin dependencias externas

#### ❌ CONTRAS

- **Requiere hardware potente**:
  - GPU mínima: 8GB VRAM (NVIDIA 3060/4060)
  - Recomendada: 12-16GB VRAM (3080/4070+)
  - CPU no es opción (extremadamente lento)
- **Calidad inferior**: Llama 3 8B bueno, pero no alcanza GPT-4/Claude
- **Mantenimiento continuo**: Actualizaciones, optimización, debugging
- **Complejidad técnica**: Configurar, integrar, monitorear
- **Coste fijo alto**: Hardware o renta servidor GPU

#### 💰 Costes proyectados

**Opción A: Servidor GPU en cloud**
- Hetzner GPU Server (RTX 4000): €100-150/mes
- RunPod GPU on-demand: €216-576/mes (si 24/7)
- Lambda Labs GPU dedicada: €110/mes
- Vast.ai (variable): €30-80/mes

**Opción B: Hardware propio**
- GPU NVIDIA 4060 Ti 16GB: ~€500
- PC/servidor completo: ~€800-1,200
- Electricidad (24/7): €20-40/mes
- **Amortización**: 6-12 meses

**Punto de equilibrio:**
- Solo viable cuando costes APIs > €100-150/mes
- Es decir: Cuando tengas ~500+ usuarios activos

---

### OPCIÓN 3: Automatización sin IA (Reglas + Python)

#### ¿Qué se puede hacer SIN IA?

**1. Categorización por reglas:**
```python
# Detectar promociones
if 'unsubscribe' in email or 'promoción' in subject:
    category = 'promotional'

# Detectar newsletters
if sender in ['newsletter@', 'noreply@']:
    category = 'newsletter'

# Detectar laborales por dominio
if '@empresa.com' in sender:
    category = 'work'
```

**2. Priorización por patrones:**
```python
# Urgente por palabras clave
urgent_keywords = ['urgente', 'asap', 'importante', 'deadline']
if any(word in subject.lower() for word in urgent_keywords):
    priority = 'high'

# Por remitente VIP
if sender in vip_contacts:
    priority = 'high'
```

**3. Estadísticas y métricas:**
- Top contactos frecuentes (SQL count)
- Días/horas más activos (groupby)
- Tiempo respuesta promedio (timestamps)
- Patrones de comunicación

**4. Detección de patrones:**
- Remitentes recurrentes con mismo tema
- Emails duplicados/similares
- Cadenas de conversación
- Adjuntos comunes por contacto

**5. Acciones automáticas:**
- Archivar promociones automáticamente
- Marcar como leído newsletters
- Organizar en carpetas por remitente
- Alertas en emails urgentes

#### ✅ PROS

- **Gratis total**: Cero costes
- **Muy rápido**: Milisegundos por email
- **Predecible**: Siempre funciona igual
- **Sin dependencias**: No APIs ni hardware especial
- **Mantenible**: Código PHP/Python estándar

#### ❌ CONTRAS

- **Sin contexto semántico**: No entiende contenido real
- **Mantenimiento manual**: Ajustar reglas continuamente
- **Sin adaptación**: No aprende de patrones complejos
- **Limitaciones claras**: No puede tareas de NLP avanzado

#### Lo que NO puede hacer sin IA:

| Tarea | Necesita IA | Alternativa con reglas |
|-------|-------------|------------------------|
| "Este email suena molesto" | ✅ Sí | ❌ Solo palabras clave básicas |
| "Resumir 10 párrafos" | ✅ Sí | ❌ Solo primeras líneas |
| "Generar respuesta apropiada" | ✅ Sí | ❌ Templates predefinidos |
| "Detectar tema principal" | ✅ Sí | 🟡 Reglas limitadas |
| "Categorizar promo/trabajo" | ❌ No | ✅ Reglas muy efectivas |
| "Detectar urgencia básica" | ❌ No | ✅ Palabras clave funcionan |

---

## ✅ DECISIÓN FINAL

### ENFOQUE HÍBRIDO PROGRESIVO

**Estrategia**: Combinar lo mejor de cada opción y evolucionar según crecimiento.

```
┌─────────────────────────────────────────────────────┐
│                                                     │
│  REGLAS (gratis, rápido, 70-80% casos)            │
│         ↓                                          │
│         ├─ ¿Email simple? → REGLAS                │
│         ├─ ¿Email complejo? → IA (Gemini/Claude)  │
│         └─ ¿Email crítico? → IA + Validación      │
│                                                     │
└─────────────────────────────────────────────────────┘
```

### ¿Por qué este enfoque?

| Ventaja | Explicación |
|---------|-------------|
| **Arranque a €0** | Gemini gratis + reglas = funcional sin coste |
| **Calidad donde importa** | IA en emails importantes, reglas en spam/promos |
| **Escalable** | Migración gradual según economía |
| **Sin compromiso** | Cambiar de proveedor cuando quieras |
| **Mejor UX** | Combinar velocidad (reglas) + inteligencia (IA) |

---

## 🚀 PLAN DE IMPLEMENTACIÓN POR FASES

### 📍 FASE 1: MVP - Validación (Mes 1-2)

#### Objetivo
Demostrar que Vera funciona y tiene valor **SIN gastar nada**.

#### Stack técnico
```
REGLAS PHP (80%) + GEMINI FLASH GRATIS (20%)
```

#### Qué construir

**1. VeraRulesService (análisis sin IA):**
- ✅ Categorización automática (trabajo/personal/promo/social)
- ✅ Priorización por palabras clave
- ✅ Detección sentimiento básico
- ✅ Estadísticas y métricas
- ✅ Top contactos y frecuencias
- ✅ Acciones automáticas (archivar promos)

**2. VeraService con Gemini (análisis avanzado):**
- ✅ Análisis sentimiento profundo (solo emails importantes)
- ✅ Resúmenes de emails largos (>500 caracteres)
- ✅ Detección intención (pregunta/info/acción)
- ✅ Tópicos principales

**3. Lógica híbrida:**
```php
// Solo usar IA si:
if ($email->priority !== 'low' && strlen($email->body) > 200) {
    // Gemini Flash (gratis)
    $analysis = $veraService->analyzeWithAI($email);
} else {
    // Reglas (gratis)
    $analysis = $veraService->analyzeWithRules($email);
}
```

#### Costes FASE 1

| Concepto | Coste/mes |
|----------|-----------|
| APIs (Gemini gratis) | €0 |
| Servidor actual | €0 |
| **TOTAL** | **€0/mes** |

#### Límites Gemini gratis
- 1,500 requests/día = 45,000/mes
- Con 500 emails/mes → Solo usas 1.1% del límite
- **Aguanta hasta ~10-20 usuarios sin problema**

#### Resultado esperado
- ✅ Vera funcional al 80% con reglas
- ✅ 20% crítico usa IA de calidad
- ✅ Usuario ve valor inmediato
- ✅ Validaste que la gente lo usa
- ✅ **Cero costes**

---

### 📍 FASE 2: Escalamiento - Primeros usuarios (Mes 3-4)

#### Objetivo
Conseguir primeros 10-50 usuarios pagando mientras mantienes costes mínimos.

#### Stack técnico
```
REGLAS (60%) + CLAUDE 3.5 SONNET (40%)
```

#### Qué cambiar

**1. Migrar de Gemini → Claude 3.5 Sonnet:**
```php
// config/vera.php
'ai_provider' => env('VERA_AI_PROVIDER', 'claude'),
'anthropic_key' => env('ANTHROPIC_API_KEY'),
'model' => 'claude-3-5-sonnet-20241022',
```

**¿Por qué Claude?**
- Mejor calidad que Gemini Pro
- Más barato que GPT-4 (€0.0008 vs €0.003)
- Excelente en español
- Respuestas más naturales

**2. Implementar caché inteligente:**
```php
// No analizar emails similares dos veces
$similar = Email::where('from_email', $email->from_email)
    ->where('subject', 'LIKE', '%' . substr($email->subject, 0, 20) . '%')
    ->whereNotNull('vera_summary')
    ->first();

if ($similar && $similar->vera_confidence > 0.8) {
    // Reusar análisis (gratis)
    $email->copyAnalysisFrom($similar);
} else {
    // Analizar con IA (€€€)
    $analysis = $veraService->analyzeEmail($email);
}
```

**3. Optimizar qué analizar:**
```php
// Promociones → Reglas (0 coste)
if ($category === 'promotional') {
    return $rulesAnalysis;
}

// Newsletters → Reglas (0 coste)
if ($category === 'newsletter') {
    return $rulesAnalysis;
}

// Trabajo/Personal → IA (costes)
return $aiAnalysis;
```

#### Costes FASE 2

**Proyección con 50 usuarios activos:**

| Concepto | Cálculo | Coste/mes |
|----------|---------|-----------|
| Emails totales | 50 usuarios × 500 emails | 25,000 emails |
| Filtrados con reglas (60%) | 15,000 emails | €0 |
| Analizados con IA (40%) | 10,000 emails | €8 |
| **TOTAL COSTES** | | **€8/mes** |
| **INGRESOS** | 50 × €10/mes | **€500/mes** |
| **MARGEN** | | **€492 (98.4%)** |

---

### 📍 FASE 3: Crecimiento - Escalar a cientos (Mes 5-7)

#### Objetivo
Manejar 100-500 usuarios optimizando costes sin comprometer calidad.

#### Stack técnico
```
REGLAS (50%) + CLAUDE 3.5 (50%) - ULTRA OPTIMIZADO
```

#### Optimizaciones adicionales

**1. Batch processing:**
```php
// Procesar múltiples emails en una llamada
$batch = Email::whereNull('vera_processed')
    ->take(10)
    ->get();

$results = $veraService->analyzeBatch($batch); // 1 API call
```

**2. Priorización inteligente:**
```php
// Solo IA para emails que usuario realmente lee
if ($email->is_unread && $email->opened_at === null) {
    // Esperar a que lo abra
    return;
}

// Si lo abrió, analizar con IA
$analysis = $veraService->analyzeEmail($email);
```

**3. Resúmenes diarios batch:**
```php
// En lugar de analizar cada email individual
// Generar resumen diario en una sola llamada
$dailySummary = $veraService->generateDailySummary($user);
// Analiza 50 emails → 1 API call (ahorro 98%)
```

#### Costes FASE 3

**Proyección con 200 usuarios activos:**

| Concepto | Cálculo | Coste/mes |
|----------|---------|-----------|
| Emails totales | 200 × 500 | 100,000 emails |
| Con reglas (50%) | 50,000 | €0 |
| Con IA (50%) | 50,000 × €0.0008 | €40 |
| **TOTAL COSTES** | | **€40/mes** |
| **INGRESOS** | 200 × €10 | **€2,000/mes** |
| **MARGEN** | | **€1,960 (98%)** |

---

### 📍 FASE 4: Híbrido Local (Mes 8-12+)

#### Objetivo
Reducir costes cuando tengas >500 usuarios implementando IA local.

#### Trigger para esta fase
```
SI (costes_apis_mes > €100) Y (ingresos_mes > €3,000):
    → Implementar IA local
SINO:
    → Seguir con APIs
```

#### Stack técnico
```
OLLAMA LOCAL (70%) + CLAUDE 3.5 (30%)
```

#### Arquitectura híbrida

```
┌────────────────────────────────────────┐
│          Email nuevo                   │
└───────────────┬────────────────────────┘
                │
                ↓
    ┌───────────────────────┐
    │   Clasificador        │
    │   (reglas rápidas)    │
    └───────┬───────────────┘
            │
            ├─ Simple/Promoción → REGLAS (gratis)
            │
            ├─ Estándar → OLLAMA LOCAL (gratis)
            │
            └─ Complejo/Crítico → CLAUDE API (€€)
```

#### Setup técnico

**1. Servidor GPU:**
- Proveedor: Hetzner GPU Server
- GPU: NVIDIA RTX 4000 (8GB VRAM)
- Coste: €120/mes
- Modelo: Llama 3 8B

**2. Instalación Ollama:**
```bash
# En servidor GPU
curl -fsSL https://ollama.com/install.sh | sh

# Descargar modelo
ollama pull llama3:8b

# Verificar
ollama run llama3:8b "test"
```

**3. Integración en VeraService:**
```php
public function analyzeEmail(Email $email): array
{
    $complexity = $this->assessComplexity($email);

    return match($complexity) {
        'simple' => $this->analyzeWithRules($email),      // Gratis
        'standard' => $this->analyzeWithOllama($email),   // Gratis (local)
        'complex' => $this->analyzeWithClaude($email),    // €€€ (API)
        'critical' => $this->analyzeWithClaude($email),   // Mejor calidad
    };
}

protected function assessComplexity(Email $email): string
{
    // Simple: Promociones, newsletters
    if (in_array($email->category, ['promotional', 'newsletter'])) {
        return 'simple';
    }

    // Crítico: Emails urgentes o de contactos VIP
    if ($email->priority === 'urgent' || $email->from_vip) {
        return 'critical';
    }

    // Complejo: Largos o múltiples temas
    if (strlen($email->body) > 1000 || $email->has_attachments) {
        return 'complex';
    }

    // Estándar: Todo lo demás
    return 'standard';
}
```

#### Costes FASE 4

**Proyección con 500 usuarios activos:**

| Concepto | Cálculo | Coste/mes |
|----------|---------|-----------|
| Emails totales | 500 × 500 | 250,000 emails |
| Con reglas (20%) | 50,000 | €0 |
| Con Ollama local (70%) | 175,000 | €0 |
| Con Claude API (10%) | 25,000 × €0.0008 | €20 |
| Servidor GPU | Hetzner | €120 |
| **TOTAL COSTES** | | **€140/mes** |
| **INGRESOS** | 500 × €10 | **€5,000/mes** |
| **MARGEN** | | **€4,860 (97.2%)** |

#### ROI Servidor GPU

```
Costes sin GPU (todo Claude API):
250,000 emails × €0.0008 = €200/mes

Costes con GPU:
- Servidor GPU: €120/mes
- 10% Claude: €20/mes
- Total: €140/mes

Ahorro: €60/mes = €720/año
```

**Conclusión**: GPU rentable desde ~400-500 usuarios.

---

## 💰 COSTES DETALLADOS - RESUMEN

### Tabla comparativa completa

| Fase | Usuarios | Emails/mes | Stack | Coste/mes | Ingreso/mes | Margen | Coste/usuario |
|------|----------|------------|-------|-----------|-------------|--------|---------------|
| **MVP** | 1-5 | 2,500 | Reglas + Gemini gratis | €0 | €0 | - | €0 |
| **Early** | 10 | 5,000 | Reglas + Gemini gratis | €0 | €100 | 100% | €0 |
| **Fase 2** | 50 | 25,000 | Reglas 60% + Claude 40% | €8 | €500 | 98.4% | €0.16 |
| **Fase 3** | 200 | 100,000 | Reglas 50% + Claude 50% | €40 | €2,000 | 98% | €0.20 |
| **Híbrido** | 500 | 250,000 | Ollama 70% + Claude 30% | €140 | €5,000 | 97.2% | €0.28 |
| **Scale** | 1,000 | 500,000 | Ollama 80% + Claude 20% | €220 | €10,000 | 97.8% | €0.22 |

### Gráfica de punto de equilibrio

```
Coste/mes (€)
  │
500│                                    ┌─ Solo APIs
  │                                  /
400│                                /
  │                              /
300│                            /
  │                          /        ┌─ Híbrido
200│                        /        /
  │                      /        /
100│    ┌─ Gemini      /        /
  │──────────────────/        /
  0│________________/________/___________  Usuarios
    0   50   100  200  300  400  500+

    ← APIs gratis/baratas  |  Híbrido rentable →
```

---

## 🏗️ ARQUITECTURA TÉCNICA

### Estructura de servicios

```
app/Services/
├── VeraService.php              # Orquestador principal
├── VeraRulesService.php         # Análisis con reglas
├── AI/
│   ├── GeminiProvider.php       # Integración Gemini
│   ├── ClaudeProvider.php       # Integración Claude
│   ├── OllamaProvider.php       # Integración Ollama (Fase 4)
│   └── AIProviderInterface.php  # Contrato común
└── Cache/
    └── VeraCacheService.php     # Caché de análisis
```

### Flujo de análisis de email

```
┌─────────────────────────────────────────────────────────┐
│                    Email nuevo                          │
└────────────────────┬────────────────────────────────────┘
                     ↓
        ┌────────────────────────┐
        │  VeraRulesService      │
        │  (análisis rápido)     │
        └────────┬───────────────┘
                 │
                 ├─ category: promotional → FIN (reglas)
                 ├─ category: newsletter → FIN (reglas)
                 └─ Necesita IA ↓
                         │
                ┌────────┴────────┐
                │  Caché check    │
                └────────┬────────┘
                         │
                ├─ Existe similar → Reusar (gratis)
                └─ No existe ↓
                         │
                ┌────────┴────────────┐
                │  VeraService        │
                │  (decidir proveedor)│
                └────────┬────────────┘
                         │
         ┌───────────────┼───────────────┐
         │               │               │
    GEMINI          CLAUDE           OLLAMA
    (gratis)        (€€)            (local)
         │               │               │
         └───────────────┴───────────────┘
                         │
                ┌────────┴────────┐
                │  Guardar en DB  │
                │  + caché        │
                └─────────────────┘
```

### Modelo de datos

```sql
-- Tabla: emails (YA EXISTE)
ALTER TABLE emails ADD COLUMN IF NOT EXISTS:
- vera_processed (boolean)
- vera_processed_at (timestamp)
- vera_summary (text)
- vera_sentiment_score (float)
- vera_sentiment_label (varchar)
- vera_priority (varchar)
- vera_tags (json)
- vera_action_required (varchar)
- vera_method (varchar) -- 'rules', 'gemini', 'claude', 'ollama'
- vera_cost (decimal) -- tracking de costes

-- Tabla nueva: vera_email_cache
CREATE TABLE vera_email_cache (
    id bigint PRIMARY KEY,
    email_pattern varchar(255), -- hash del patrón
    from_email varchar(255),
    subject_pattern varchar(255),
    analysis_result json,
    confidence_score float,
    times_reused int DEFAULT 0,
    created_at timestamp,
    INDEX (email_pattern),
    INDEX (from_email)
);

-- Tabla nueva: vera_usage_stats
CREATE TABLE vera_usage_stats (
    id bigint PRIMARY KEY,
    user_id bigint,
    date date,
    total_emails int,
    analyzed_with_rules int,
    analyzed_with_ai int,
    provider_used varchar(50), -- 'gemini', 'claude', 'ollama'
    total_tokens int,
    estimated_cost decimal(10,4),
    created_at timestamp,
    INDEX (user_id, date)
);
```

---

## 🔄 MIGRACIÓN ENTRE FASES

### De FASE 1 → FASE 2 (Gemini → Claude)

**Trigger**: Cuando superas límite gratis Gemini (1,500 requests/día).

**Pasos**:

1. **Obtener API key de Anthropic:**
   - Ir a: https://console.anthropic.com/
   - Crear cuenta → API Keys → Create Key
   - Agregar $10 créditos

2. **Actualizar .env:**
   ```env
   VERA_AI_PROVIDER=claude
   ANTHROPIC_API_KEY=sk-ant-tu-key-aqui
   VERA_AI_MODEL=claude-3-5-sonnet-20241022
   ```

3. **Instalar SDK:**
   ```bash
   composer require anthropics/anthropic-sdk-php
   ```

4. **No cambiar código**: `VeraService` ya soporta múltiples proveedores.

5. **Verificar:**
   ```bash
   php artisan tinker
   $vera = new App\Services\VeraService();
   $vera->testConnection(); // Debe retornar OK
   ```

**Tiempo estimado**: 15 minutos
**Downtime**: 0 (cambio en caliente)

---

### De FASE 2/3 → FASE 4 (APIs → Híbrido Local)

**Trigger**: Cuando costes APIs > €100/mes y ingresos > €3,000/mes.

**Pasos**:

1. **Contratar servidor GPU:**
   ```
   Hetzner Cloud GPU
   - Plan: CX41 + GPU
   - GPU: NVIDIA RTX 4000 (8GB)
   - RAM: 16GB
   - Coste: ~€120/mes
   ```

2. **Instalar Ollama:**
   ```bash
   # SSH al servidor GPU
   ssh root@tu-servidor-gpu

   # Instalar Ollama
   curl -fsSL https://ollama.com/install.sh | sh

   # Descargar modelo
   ollama pull llama3:8b

   # Verificar GPU
   nvidia-smi

   # Exponer API
   OLLAMA_HOST=0.0.0.0:11434 ollama serve &
   ```

3. **Configurar firewall:**
   ```bash
   # Permitir solo tu servidor Laravel
   ufw allow from TU_IP_LARAVEL to any port 11434
   ```

4. **Actualizar .env Laravel:**
   ```env
   VERA_AI_PROVIDER=hybrid
   OLLAMA_API_URL=http://tu-servidor-gpu:11434
   OLLAMA_MODEL=llama3:8b

   # Mantener Claude para casos complejos
   ANTHROPIC_API_KEY=sk-ant-...
   ```

5. **Crear OllamaProvider:**
   ```php
   // app/Services/AI/OllamaProvider.php
   // (código ya preparado en VeraService)
   ```

6. **Testing gradual:**
   ```php
   // Empezar con 10% a Ollama
   if (rand(1, 100) <= 10) {
       $analysis = $ollama->analyze($email);
   } else {
       $analysis = $claude->analyze($email);
   }

   // Monitorear calidad
   // Si OK → Subir a 50%, luego 70%
   ```

**Tiempo estimado**: 4-6 horas
**Downtime**: 0 (despliegue gradual)

---

## 📚 REFERENCIAS Y RECURSOS

### APIs de IA

| Proveedor | Docs | Pricing | Dashboard |
|-----------|------|---------|-----------|
| **Google Gemini** | [ai.google.dev/docs](https://ai.google.dev/docs) | [Gratis 1.5K/día](https://ai.google.dev/pricing) | [aistudio.google.com](https://aistudio.google.com) |
| **Anthropic Claude** | [docs.anthropic.com](https://docs.anthropic.com) | [€3-15/1M tokens](https://www.anthropic.com/pricing) | [console.anthropic.com](https://console.anthropic.com) |
| **OpenAI** | [platform.openai.com/docs](https://platform.openai.com/docs) | [€10-30/1M tokens](https://openai.com/pricing) | [platform.openai.com](https://platform.openai.com) |

### IA Local

| Herramienta | Uso | Docs |
|-------------|-----|------|
| **Ollama** | Runtime local LLMs | [ollama.com/docs](https://ollama.com/docs) |
| **Llama 3** | Modelo de Meta | [llama.meta.com](https://llama.meta.com) |
| **Hugging Face** | Modelos y datasets | [huggingface.co/docs](https://huggingface.co/docs) |

### Servidores GPU

| Proveedor | Tipo | Precio | Enlace |
|-----------|------|--------|--------|
| **Hetzner** | Dedicado | €120/mes | [hetzner.com/gpu](https://www.hetzner.com/cloud) |
| **RunPod** | On-demand | €0.30/hora | [runpod.io](https://runpod.io) |
| **Vast.ai** | Marketplace | €0.20/hora | [vast.ai](https://vast.ai) |
| **Lambda Labs** | Dedicado | €110/mes | [lambdalabs.com](https://lambdalabs.com) |

### Librerías PHP

```bash
# Google Gemini
composer require google/generative-ai-php

# Anthropic Claude
composer require anthropics/anthropic-sdk-php

# OpenAI
composer require openai-php/client

# Cliente HTTP genérico
composer require guzzlehttp/guzzle
```

---

## 🎯 PRÓXIMOS PASOS INMEDIATOS

### Checklist para empezar AHORA

- [ ] Crear `VeraRulesService.php` (análisis sin IA)
- [ ] Integrar Gemini Flash (gratis)
- [ ] Modificar `VeraService.php` (lógica híbrida)
- [ ] Crear migraciones para tablas de caché
- [ ] Actualizar `PLAN_IMPLEMENTACION_VERA.md`
- [ ] Testing con tus 4,163 emails (€0 coste)
- [ ] Dashboard para ver estadísticas de uso
- [ ] Monitoreo de costes por proveedor

### Comando rápido para empezar

```bash
# 1. Obtener API key Gemini (gratis)
# → https://aistudio.google.com/apikey

# 2. Añadir a .env
echo "VERA_AI_PROVIDER=gemini" >> .env
echo "GEMINI_API_KEY=tu-key-aqui" >> .env

# 3. Instalar librería
composer require google/generative-ai-php

# 4. Crear servicios (código listo en sección anterior)

# 5. Probar
php artisan tinker
> $vera = new App\Services\VeraService();
> $email = App\Models\Email::first();
> $vera->analyzeEmail($email);
```

---

## 📊 MÉTRICAS A MONITOREAR

### KPIs por fase

| Métrica | Objetivo Fase 1 | Objetivo Fase 2 | Objetivo Fase 3 | Objetivo Fase 4 |
|---------|-----------------|-----------------|-----------------|-----------------|
| **Usuarios activos** | 1-10 | 10-50 | 50-200 | 500+ |
| **Emails/mes** | <10K | 10K-50K | 50K-150K | 250K+ |
| **Coste/email** | €0 | <€0.001 | <€0.0005 | <€0.0003 |
| **% Análisis IA** | 20% | 40% | 50% | 30% |
| **Precisión reglas** | >70% | >75% | >80% | >85% |
| **Latencia p95** | <2s | <3s | <2s | <1s |
| **Uptime** | >95% | >99% | >99.5% | >99.9% |

### Dashboard de costes

```sql
-- Query para tracking de costes
SELECT
    DATE(created_at) as date,
    vera_method,
    COUNT(*) as emails_count,
    SUM(vera_cost) as total_cost,
    AVG(vera_cost) as avg_cost_per_email
FROM emails
WHERE vera_processed = true
GROUP BY DATE(created_at), vera_method
ORDER BY date DESC;
```

---

## ✅ CONCLUSIÓN

### Decisión final

**EMPEZAMOS CON: Enfoque Híbrido Progresivo**

1. **Fase 1 (AHORA)**: Reglas + Gemini gratis → €0/mes
2. **Fase 2 (Mes 3-4)**: Reglas + Claude optimizado → €8-40/mes
3. **Fase 3 (Mes 5-7)**: Optimizaciones avanzadas → €40-100/mes
4. **Fase 4 (Mes 8+)**: Ollama local + Claude → €140-220/mes

### Ventajas de este plan

✅ Arranque sin inversión (€0)
✅ Escalamiento gradual según ingresos
✅ Flexibilidad para cambiar proveedores
✅ Márgenes >95% en todas las fases
✅ Sin lock-in tecnológico
✅ Migración sin downtime

### Siguiente paso

**VER**: `PLAN_IMPLEMENTACION_VERA.md` - Checkpoint 1

Comenzamos la implementación de VeraRulesService y la integración con Gemini.

---

**Última actualización**: 14 Diciembre 2025
**Versión**: 1.0
**Estado**: ✅ ESTRATEGIA APROBADA - LISTA PARA IMPLEMENTAR
