# 🤖 PLAN DE IMPLEMENTACIÓN DE VERA
## Asistente Personal con IA - Paso a Paso

**Fecha creación**: 09 Diciembre 2025
**Estado del proyecto**: ✅ Sistema de emails FUNCIONAL y ESTABLE
**Objetivo**: Crear a Vera sin romper nada existente

---


 # ✅ ¡PERFECTO! Checkpoints completados

  ## 🎉 LO QUE HEMOS LOGRADO HOY:

 ### 1. ✅ Creado config/vera.php - Configuración completa de Vera
 ### 2. ✅ Variables en .env - VERA_ENABLED=false (seguro, no afecta nada)
 ### 3. ✅ Validado que funciona - Config se lee correctamente
 ### 4. ✅ Sistema intacto - 4,163 emails y 1 cuenta funcionando perfectamente

 # 🔒 ESTADO ACTUAL:

  ## ✅ Vera configurada (pero DESACTIVADA)
  ## ✅ Sistema de emails funcionando normal
  ## ✅ No se rompió nada
  ## ✅ Base lista para continuar

  ---
 # 📝 SIGUIENTE SESIÓN:

  ## Cuando tengas más tiempo, continuamos con:

  ### 1. Instalar dependencia OpenAI (1 comando)
  ### 2. Crear VeraService (cerebro de Vera - 300 líneas ya escritas en el plan)
  ### 3. Crear Job de procesamiento (ya escrito en el plan)
  ### 4. Integrar en sincronización (solo 5 líneas)

  ### Todo el código está listo en PLAN_IMPLEMENTACION_VERA.md - solo hay que copiarlo y validar.

  ---
 # 📊 PROGRESO GENERAL:

 # FASE 1: Fundamentos
  ## ├─ ✅ Checkpoint 1: Config creado
  ## ├─ ✅ Checkpoint 2: Variables .env
  ## ├─ ✅ Checkpoint 3: Validación
  ## ├─ ⏳ Checkpoint 4: Instalar OpenAI (próxima sesión)
  ## ├─ ⏳ Checkpoint 5: VeraService
  ## ├─ ⏳ Checkpoint 6: Job
  ## └─ ⏳ Checkpoint 7: Integración

 # Completado: 3/7 (42%)

  ---
  # 🎯 RESUMEN:

  # Has sentado las bases perfectas para Vera hoy:
  # - Identidad y personalidad definida
  # - Configuración lista y validada
  # - Sistema protegido (Vera desactivada hasta que esté completa)
  # - Plan detallado para continuar

  # Cuando vuelvas, solo seguimos el plan paso a paso. Cada checkpoint está documentado con código completo y validaciones.

  ---
  

## 📊 RESUMEN EJECUTIVO

### ¿Qué tienes ahora?
- ✅ Gmail OAuth funcionando
- ✅ Sincronización inicial completa
- ✅ Sincronización incremental cada 2 minutos
- ✅ Vista de emails con filtros y búsqueda
- ✅ Base de datos CON CAMPOS VERA preparados
- ✅ Modelos con relaciones correctas

### ¿Qué vamos a crear?
- 🤖 **Vera**: Asistente IA que analiza emails automáticamente
- 📊 **Dashboard**: Insights inteligentes del usuario
- 💬 **Chat**: Interfaz conversacional con Vera
- 🧠 **Análisis**: Sentimiento, prioridad, temas
- 🎯 **Automatización**: Respuestas y acciones inteligentes

### Principios clave
1. **NO ROMPER NADA**: Cada cambio es aditivo
2. **PASO A PASO**: Un componente a la vez
3. **VALIDAR SIEMPRE**: Testear antes de continuar
4. **INCREMENTAL**: Funcionalidad básica primero, luego sofisticación

---

## 🗺️ ROADMAP COMPLETO

### FASE 1: FUNDAMENTOS (Semana 1)
- [x] Análisis completo del proyecto ✅
- [ ] Definir identidad de Vera
- [ ] Crear servicio base de IA
- [ ] Primera integración (análisis simple)

### FASE 2: ANÁLISIS AUTOMÁTICO (Semana 2)
- [ ] Análisis de sentimiento
- [ ] Detección de temas
- [ ] Priorización inteligente
- [ ] Dashboard de insights

### FASE 3: INTERFAZ CONVERSACIONAL (Semana 3)
- [ ] Chat con Vera
- [ ] Comandos básicos
- [ ] Búsqueda natural
- [ ] Acciones desde chat

### FASE 4: AUTOMATIZACIÓN (Semana 4)
- [ ] Perfiles de contactos
- [ ] Respuestas sugeridas
- [ ] Reglas automáticas
- [ ] Aprendizaje continuo

---

## 📋 FASE 1: FUNDAMENTOS - PASO A PASO

### CHECKPOINT 1: Definir identidad de Vera

#### 1.1. Crear archivo de configuración

**Archivo**: `config/vera.php`

```php
<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Vera AI Assistant Settings
    |--------------------------------------------------------------------------
    |
    | Configuración del asistente virtual Vera
    |
    */

    // Activar/desactivar Vera globalmente
    'enabled' => env('VERA_ENABLED', false),

    // Proveedor de IA
    'ai_provider' => env('VERA_AI_PROVIDER', 'openai'), // openai, anthropic, local

    // API Keys
    'openai_key' => env('OPENAI_API_KEY'),
    'anthropic_key' => env('ANTHROPIC_API_KEY'),

    // Modelo a usar
    'model' => env('VERA_AI_MODEL', 'gpt-4'),

    // Personalidad de Vera
    'personality' => [
        'name' => 'Vera',
        'role' => 'Asistente personal de email',
        'tone' => 'profesional pero amigable',
        'language' => 'es',
    ],

    // Límites de procesamiento
    'limits' => [
        'max_emails_per_batch' => 50,
        'max_tokens_per_request' => 2000,
        'retry_attempts' => 3,
    ],

    // Features activadas
    'features' => [
        'sentiment_analysis' => true,
        'topic_detection' => true,
        'priority_detection' => true,
        'auto_categorization' => true,
        'summary_generation' => true,
        'response_suggestions' => false, // Fase 4
        'auto_replies' => false,         // Fase 4
    ],

    // Thresholds de confianza
    'confidence' => [
        'min_for_auto_action' => 0.85,  // 85% para acción automática
        'min_for_suggestion' => 0.60,   // 60% para sugerencia
    ],

];
```

**Acción**: Crear este archivo y agregarlo al proyecto

**Validación**:
```bash
php artisan config:clear
php artisan tinker
> config('vera.enabled')
=> false
```

---

#### 1.2. Agregar variables al .env

**Archivo**: `.env`

```env
# Vera AI Assistant
VERA_ENABLED=false
VERA_AI_PROVIDER=openai
VERA_AI_MODEL=gpt-4-turbo-preview
OPENAI_API_KEY=tu_api_key_aqui

# Alternativamente para Anthropic Claude
# VERA_AI_PROVIDER=anthropic
# ANTHROPIC_API_KEY=tu_api_key_aqui
```

**Acción**: Agregar estas líneas al final de tu `.env`

**Nota**: De momento deja `VERA_ENABLED=false` hasta que esté todo listo

---

#### 1.3. Instalar dependencia de OpenAI

**Comando**:
```bash
composer require openai-php/client
```

**Validación**:
```bash
composer show openai-php/client
```

---

### CHECKPOINT 2: Crear VeraService (Cerebro de Vera)

#### 2.1. Crear el servicio base

**Archivo**: `app/Services/VeraService.php`

```php
<?php

namespace App\Services;

use App\Models\Email;
use App\Models\VeraEmailAction;
use Illuminate\Support\Facades\Log;
use OpenAI;

class VeraService
{
    protected $client;
    protected $model;

    public function __construct()
    {
        // Solo inicializar si está habilitado
        if (config('vera.enabled')) {
            $this->client = OpenAI::client(config('vera.openai_key'));
            $this->model = config('vera.model');
        }
    }

    /**
     * Verificar si Vera está habilitada
     */
    public function isEnabled(): bool
    {
        return config('vera.enabled') && !empty(config('vera.openai_key'));
    }

    /**
     * Analizar un email con IA
     *
     * @param Email $email
     * @return array
     */
    public function analyzeEmail(Email $email): array
    {
        if (!$this->isEnabled()) {
            Log::info('Vera está deshabilitada, saltando análisis');
            return $this->getEmptyAnalysis();
        }

        try {
            Log::info('🤖 Vera analizando email', ['email_id' => $email->id]);

            // Construir prompt
            $prompt = $this->buildAnalysisPrompt($email);

            // Llamar a OpenAI
            $response = $this->client->chat()->create([
                'model' => $this->model,
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => $this->getSystemPrompt()
                    ],
                    [
                        'role' => 'user',
                        'content' => $prompt
                    ]
                ],
                'max_tokens' => config('vera.limits.max_tokens_per_request'),
                'temperature' => 0.7,
            ]);

            // Parsear respuesta
            $content = $response->choices[0]->message->content;
            $analysis = $this->parseAnalysisResponse($content);

            Log::info('✅ Análisis completado', [
                'email_id' => $email->id,
                'sentiment' => $analysis['sentiment_label']
            ]);

            return $analysis;

        } catch (\Exception $e) {
            Log::error('❌ Error en análisis de Vera', [
                'email_id' => $email->id,
                'error' => $e->getMessage()
            ]);

            return $this->getEmptyAnalysis();
        }
    }

    /**
     * Construir prompt de análisis
     */
    protected function buildAnalysisPrompt(Email $email): string
    {
        return <<<PROMPT
Analiza el siguiente email y devuelve un JSON con esta estructura:

{
  "summary": "Resumen corto (máximo 150 caracteres)",
  "sentiment_score": 0.0 a 1.0 (0=negativo, 0.5=neutral, 1=positivo),
  "sentiment_label": "positive|neutral|negative|urgent",
  "priority": "low|normal|high|urgent",
  "topics": ["tema1", "tema2", "tema3"],
  "action_required": "none|reply|schedule|forward|archive",
  "confidence": 0.0 a 1.0
}

EMAIL:
De: {$email->from_email} ({$email->from_name})
Para: {$email->to_email}
Asunto: {$email->subject}
Contenido:
{$this->getEmailContent($email)}

Responde SOLO con el JSON, sin markdown ni explicaciones.
PROMPT;
    }

    /**
     * Prompt del sistema (personalidad de Vera)
     */
    protected function getSystemPrompt(): string
    {
        return <<<SYSTEM
Eres Vera, una asistente personal inteligente de email.

Tu tarea es analizar emails y proporcionar insights útiles a tu usuario.

Características:
- Eres profesional pero amigable
- Entiendes español perfectamente
- Detectas sentimientos y prioridades
- Extraes temas principales
- Sugieres acciones claras

Siempre respondes en formato JSON válido.
SYSTEM;
    }

    /**
     * Obtener contenido del email (limitado)
     */
    protected function getEmailContent(Email $email): string
    {
        // Preferir texto plano, sino HTML sin tags
        $content = $email->body_text ?? strip_tags($email->body_html ?? '');

        // Limitar a 1000 caracteres para no gastar muchos tokens
        return mb_substr($content, 0, 1000);
    }

    /**
     * Parsear respuesta JSON de la IA
     */
    protected function parseAnalysisResponse(string $content): array
    {
        try {
            // Limpiar markdown si existe
            $content = preg_replace('/```json\s*|\s*```/', '', $content);
            $content = trim($content);

            $data = json_decode($content, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('JSON inválido: ' . json_last_error_msg());
            }

            return [
                'summary' => $data['summary'] ?? '',
                'sentiment_score' => (float) ($data['sentiment_score'] ?? 0.5),
                'sentiment_label' => $data['sentiment_label'] ?? 'neutral',
                'priority' => $data['priority'] ?? 'normal',
                'topics' => $data['topics'] ?? [],
                'action_required' => $data['action_required'] ?? 'none',
                'confidence' => (float) ($data['confidence'] ?? 0.0),
            ];

        } catch (\Exception $e) {
            Log::warning('Error parseando respuesta de IA', [
                'error' => $e->getMessage(),
                'content' => $content
            ]);

            return $this->getEmptyAnalysis();
        }
    }

    /**
     * Análisis vacío (fallback)
     */
    protected function getEmptyAnalysis(): array
    {
        return [
            'summary' => '',
            'sentiment_score' => 0.5,
            'sentiment_label' => 'neutral',
            'priority' => 'normal',
            'topics' => [],
            'action_required' => 'none',
            'confidence' => 0.0,
        ];
    }

    /**
     * Guardar análisis en el email
     */
    public function saveAnalysisToEmail(Email $email, array $analysis): void
    {
        $email->update([
            'vera_summary' => $analysis['summary'],
            'vera_sentiment_score' => $analysis['sentiment_score'],
            'vera_tags' => $analysis['topics'],
            'vera_action_required' => $analysis['action_required'],
            'vera_processed' => true,
            'vera_processed_at' => now(),
        ]);

        // También actualizar prioridad si es alta
        if ($analysis['priority'] === 'urgent' || $analysis['priority'] === 'high') {
            $email->update(['priority' => $analysis['priority']]);
        }

        Log::info('💾 Análisis guardado en email', ['email_id' => $email->id]);
    }

    /**
     * Crear acción de Vera
     */
    public function createAction(Email $email, array $analysis, string $actionType): VeraEmailAction
    {
        return VeraEmailAction::create([
            'email_id' => $email->id,
            'user_id' => $email->user_id,
            'action_type' => $actionType,
            'action_description' => "Vera analizó el email y detectó: {$analysis['sentiment_label']}",
            'action_data' => $analysis,
            'reasoning' => "Análisis automático basado en contenido del email",
            'confidence_score' => $analysis['confidence'],
            'user_feedback' => 'pending',
        ]);
    }
}
```

**Acción**: Crear este archivo exactamente como está

**Validación**:
```bash
# Verificar sintaxis PHP
php -l app/Services/VeraService.php

# Probar en tinker
php artisan tinker
> $vera = new App\Services\VeraService();
> $vera->isEnabled()
=> false  # Correcto (aún está deshabilitado)
```

---

### CHECKPOINT 3: Crear Job de procesamiento

#### 3.1. Crear ProcessEmailWithVera Job

**Comando**:
```bash
php artisan make:job ProcessEmailWithVera
```

**Archivo**: `app/Jobs/ProcessEmailWithVera.php`

```php
<?php

namespace App\Jobs;

use App\Models\Email;
use App\Services\VeraService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessEmailWithVera implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 60; // 1 minuto

    protected Email $email;

    /**
     * Create a new job instance.
     */
    public function __construct(Email $email)
    {
        $this->email = $email;
    }

    /**
     * Execute the job.
     */
    public function handle(VeraService $veraService): void
    {
        // Verificar que Vera esté habilitada
        if (!$veraService->isEnabled()) {
            Log::info('⏸️  Vera deshabilitada, saltando procesamiento', [
                'email_id' => $this->email->id
            ]);
            return;
        }

        // Verificar que no esté ya procesado
        if ($this->email->vera_processed) {
            Log::info('⏭️  Email ya procesado por Vera', [
                'email_id' => $this->email->id
            ]);
            return;
        }

        try {
            Log::info('🔄 Procesando email con Vera', [
                'email_id' => $this->email->id,
                'subject' => $this->email->subject
            ]);

            // Analizar email
            $analysis = $veraService->analyzeEmail($this->email);

            // Guardar análisis
            $veraService->saveAnalysisToEmail($this->email, $analysis);

            // Crear acción de Vera
            $veraService->createAction($this->email, $analysis, 'analyzed');

            Log::info('✅ Email procesado exitosamente', [
                'email_id' => $this->email->id,
                'sentiment' => $analysis['sentiment_label'],
                'priority' => $analysis['priority']
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Error procesando email con Vera', [
                'email_id' => $this->email->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // No marcar como procesado si falló
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('💀 Job de Vera falló después de todos los intentos', [
            'email_id' => $this->email->id,
            'error' => $exception->getMessage()
        ]);

        // Opcional: notificar al admin
    }
}
```

**Acción**: Crear este archivo

**Validación**:
```bash
php -l app/Jobs/ProcessEmailWithVera.php
```

---

### CHECKPOINT 4: Integrar en sincronización (SIN ROMPER NADA)

#### 4.1. Modificar GmailSyncService

**Archivo**: `app/Services/GmailSyncService.php`

**Buscar la línea** ~270 (en el método `processGmailMessage`):

```php
if ($email) {
    $email->update($emailData);
    return ['action' => 'updated', 'email' => $email];
} else {
    $email = Email::create($emailData);
    return ['action' => 'created', 'email' => $email];
}
```

**Reemplazar por:**

```php
if ($email) {
    $email->update($emailData);
    $action = 'updated';
} else {
    $email = Email::create($emailData);
    $action = 'created';
}

// 🤖 NUEVO: Procesar con Vera si está habilitado
if (config('vera.enabled')) {
    \App\Jobs\ProcessEmailWithVera::dispatch($email);
}

return ['action' => $action, 'email' => $email];
```

**Acción**: Modificar solo esas líneas

**Validación**:
```bash
# Verificar sintaxis
php -l app/Services/GmailSyncService.php

# La sincronización debe seguir funcionando igual
# (Vera aún está deshabilitado)
```

---

### CHECKPOINT 5: Probar sin activar Vera

#### 5.1. Verificar que todo funciona SIN Vera

**Test 1: Sincronizar emails**
```bash
# Sincronizar una cuenta manualmente
php artisan tinker
> $account = App\Models\EmailAccount::first();
> App\Jobs\SyncEmailAccount::dispatch($account, 'incremental');
```

**Resultado esperado**:
- ✅ Emails se sincronizan normalmente
- ✅ NO se procesan con Vera (está deshabilitada)
- ✅ No hay errores en logs

**Test 2: Ver emails en UI**
```
1. Abrir navegador → http://localhost/emails
2. Verificar que se muestran emails
3. Verificar que no hay errores
```

---

### CHECKPOINT 6: ¡Activar Vera por primera vez!

#### 6.1. Obtener API Key de OpenAI

1. Ir a https://platform.openai.com/api-keys
2. Crear nueva API key
3. Copiar el valor (empieza con `sk-...`)

#### 6.2. Configurar .env

**Archivo**: `.env`

```env
# Cambiar estas líneas:
VERA_ENABLED=true                    # ✅ Activar
OPENAI_API_KEY=sk-tu-key-aqui       # ✅ Tu key real
```

**Acción**: Guardar y reiniciar

```bash
php artisan config:clear
php artisan queue:restart
```

#### 6.3. Probar Vera manualmente

```bash
php artisan tinker
```

```php
// Obtener un email
$email = App\Models\Email::where('vera_processed', false)->first();

// Crear instancia de Vera
$vera = new App\Services\VeraService();

// Verificar que está habilitada
$vera->isEnabled();
// => true ✅

// Analizar email
$analysis = $vera->analyzeEmail($email);

// Ver resultado
print_r($analysis);
// Deberías ver:
// Array (
//   [summary] => "..."
//   [sentiment_score] => 0.8
//   [sentiment_label] => "positive"
//   [priority] => "normal"
//   [topics] => Array (...)
//   [action_required] => "reply"
//   [confidence] => 0.85
// )

// Guardar en email
$vera->saveAnalysisToEmail($email, $analysis);

// Verificar que se guardó
$email->fresh();
$email->vera_summary;
$email->vera_sentiment_score;
$email->vera_processed;
// => true ✅
```

**Resultado esperado**:
- ✅ API de OpenAI responde
- ✅ Análisis se genera correctamente
- ✅ Se guarda en la base de datos
- ✅ Email marcado como procesado

---

#### 6.4. Probar con Job en cola

```bash
# Terminal 1: Worker de cola
php artisan queue:work

# Terminal 2: Dispatch job
php artisan tinker
```

```php
$email = App\Models\Email::where('vera_processed', false)->first();
App\Jobs\ProcessEmailWithVera::dispatch($email);
```

**Ver en Terminal 1**:
```
[2025-12-09 10:30:15] Processing: App\Jobs\ProcessEmailWithVera
🤖 Vera analizando email...
✅ Email procesado exitosamente
[2025-12-09 10:30:18] Processed: App\Jobs\ProcessEmailWithVera
```

---

### CHECKPOINT 7: Probar sincronización automática con Vera

#### 7.1. Forzar sincronización incremental

```bash
# Con Vera activada, sincronizar
php artisan tinker
```

```php
$account = App\Models\EmailAccount::first();
App\Jobs\SyncEmailAccount::dispatch($account, 'incremental');
```

**Resultado esperado**:
- ✅ Emails nuevos se sincronizan
- ✅ Cada email se encola para procesamiento con Vera
- ✅ Worker procesa cada uno
- ✅ Campos vera_* se llenan automáticamente

#### 7.2. Ver emails procesados en base de datos

```bash
php artisan tinker
```

```php
// Contar emails procesados
App\Models\Email::where('vera_processed', true)->count();

// Ver algunos ejemplos
App\Models\Email::where('vera_processed', true)
    ->select('id', 'subject', 'vera_summary', 'vera_sentiment_score', 'vera_tags')
    ->take(5)
    ->get();
```

---

### ✅ CHECKPOINT FINAL FASE 1

**Validaciones antes de continuar a Fase 2:**

- [ ] VeraService creado y funcionando
- [ ] ProcessEmailWithVera Job creado
- [ ] Integración en GmailSyncService (5 líneas)
- [ ] Configuración en config/vera.php
- [ ] Variables en .env configuradas
- [ ] Vera analiza emails correctamente
- [ ] Análisis se guarda en base de datos
- [ ] NO hay errores en logs
- [ ] Sistema de sincronización funciona igual que antes

**Si todo está ✅, estás listo para Fase 2: Dashboard e Insights**

---

## 🎯 SIGUIENTE: FASE 2 - DASHBOARD E INSIGHTS

En la próxima fase crearemos:
1. Dashboard con estadísticas de Vera
2. Vista de emails con análisis
3. Filtros por sentimiento y prioridad
4. Lista de acciones pendientes

**¿Listo para continuar?**

