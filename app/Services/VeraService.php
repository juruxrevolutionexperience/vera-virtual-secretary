<?php

namespace App\Services;

use App\Models\Email;
use App\Services\AI\GeminiProvider;
use App\Services\VeraUsageService;
use App\Exceptions\UsageLimitExceededException;
use Illuminate\Support\Facades\Log;

/**
 * VeraService - Orquestador principal
 *
 * Decide cuándo usar reglas y cuándo usar IA
 * Estrategia híbrida: Reglas (80%) + IA (20%)
 */
class VeraService
{
    protected VeraRulesService $rulesService;
    protected VeraUsageService $usageService;
    protected ?GeminiProvider $geminiProvider = null;
    protected string $aiProvider;

    public function __construct()
    {
        $this->rulesService = new VeraRulesService();
        $this->usageService = new VeraUsageService();
        $this->aiProvider = config('vera.ai_provider', 'gemini');

        // Inicializar provider de IA si está habilitado
        if ($this->isEnabled() && $this->aiProvider === 'gemini') {
            $this->geminiProvider = new GeminiProvider();
        }
    }

    /**
     * Verificar si Vera está habilitada
     *
     * @return bool
     */
    public function isEnabled(): bool
    {
        return config('vera.enabled', false);
    }

    /**
     * Analizar email (MÉTODO PRINCIPAL)
     *
     * Flujo:
     * 1. Análisis con reglas (gratis, rápido)
     * 2. Decidir si necesita IA
     * 3. Verificar límites del usuario
     * 4. Si necesita IA Y tiene límite → Gemini, sino → reglas
     *
     * @param Email $email
     * @return array
     */
    public function analyzeEmail(Email $email): array
    {
        if (!$this->isEnabled()) {
            Log::info('⏸️  Vera deshabilitada, saltando análisis', [
                'email_id' => $email->id
            ]);
            return $this->getEmptyAnalysis();
        }

        try {
            Log::info('🔍 Iniciando análisis de email', [
                'email_id' => $email->id,
                'subject' => $email->subject,
                'from' => $email->from_email
            ]);

            // PASO 1: Análisis con reglas (SIEMPRE - gratis)
            $rulesAnalysis = $this->rulesService->analyzeEmail($email);

            // PASO 2: ¿Necesita IA?
            $needsAI = $this->rulesService->needsAI($email, $rulesAnalysis);

            if (!$needsAI) {
                // Email simple, usar solo reglas (gratis)
                Log::info('📊 Usando solo análisis con reglas', [
                    'email_id' => $email->id,
                    'reason' => 'Email no requiere IA'
                ]);
                return $rulesAnalysis;
            }

            // PASO 3: Verificar límites del usuario
            $user = $email->user;
            if (!$this->usageService->canProcessEmail($user)) {
                Log::warning('🚫 Usuario sin límite disponible, usando solo reglas', [
                    'email_id' => $email->id,
                    'user_id' => $user->id,
                    'plan' => $user->plan_type,
                    'emails_used' => $this->usageService->getEmailsProcessedThisMonth($user),
                    'limit' => $user->monthly_email_limit,
                ]);

                // Marcar que se saltó IA por límite
                $email->update(['vera_ai_skipped' => true]);

                return array_merge($rulesAnalysis, [
                    'method' => 'rules',
                    'ai_skipped' => true,
                    'skip_reason' => 'usage_limit_exceeded',
                ]);
            }

            // PASO 4: Analizar con IA (usuario tiene límite disponible)
            if ($this->geminiProvider && $this->geminiProvider->isConfigured()) {
                Log::info('🤖 Email necesita análisis con IA', [
                    'email_id' => $email->id,
                    'user_id' => $user->id,
                    'emails_remaining' => $this->usageService->getRemainingEmails($user),
                ]);

                $aiAnalysis = $this->analyzeWithAI($email);

                // Registrar email como procesado
                $this->usageService->recordEmailProcessing($user, $email, 'email_analysis');

                // Combinar análisis: IA tiene prioridad
                return array_merge($rulesAnalysis, $aiAnalysis, [
                    'method' => 'ai',
                    'ai_provider' => 'gemini'
                ]);

            } else {
                // IA no configurada, usar reglas
                Log::info('📊 IA no configurada, usando reglas', [
                    'email_id' => $email->id,
                ]);
                return $rulesAnalysis;
            }

        } catch (\Exception $e) {
            Log::error('❌ Error analizando email', [
                'email_id' => $email->id,
                'error' => $e->getMessage()
            ]);

            // Fallback: análisis con reglas
            return $this->rulesService->analyzeEmail($email);
        }
    }

    /**
     * Analizar email con IA (Gemini)
     *
     * @param Email $email
     * @return array
     */
    protected function analyzeWithAI(Email $email): array
    {
        try {
            // Construir prompts
            $systemPrompt = $this->getSystemPrompt();
            $userPrompt = $this->buildAnalysisPrompt($email);

            // Llamar a Gemini con registro automático de tokens
            $response = $this->geminiProvider->analyze(
                systemPrompt: $systemPrompt,
                userPrompt: $userPrompt,
                userId: $email->user_id,
                emailId: $email->id,
                actionType: 'email_analysis',
                metadata: [
                    'subject' => $email->subject,
                    'from' => $email->from_email,
                    'content_length' => strlen($email->body_text ?? $email->body_html ?? ''),
                ]
            );

            // Parsear respuesta JSON
            $analysis = $this->parseAIResponse($response['content']);

            // Agregar metadata
            $analysis['tokens_used'] = $response['tokens_used'] ?? 0;
            $analysis['cost'] = 0.0; // Gratis con Gemini Flash

            Log::info('✅ Análisis con IA completado', [
                'email_id' => $email->id,
                'tokens' => $analysis['tokens_used'],
                'sentiment' => $analysis['sentiment_label'] ?? 'unknown'
            ]);

            return $analysis;

        } catch (\Exception $e) {
            Log::error('❌ Error en análisis con IA, usando reglas como fallback', [
                'email_id' => $email->id,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    /**
     * Construir prompt de análisis
     *
     * @param Email $email
     * @return string
     */
    protected function buildAnalysisPrompt(Email $email): string
    {
        $content = $this->getEmailContent($email);

        return <<<PROMPT
Analiza el siguiente email y devuelve un JSON con esta estructura EXACTA:

{
  "summary": "Resumen corto en español (máximo 150 caracteres)",
  "sentiment_score": 0.0 a 1.0 (0=muy negativo, 0.5=neutral, 1=muy positivo),
  "sentiment_label": "positive|neutral|negative|urgent",
  "priority": "low|normal|high|urgent",
  "topics": ["tema1", "tema2", "tema3"],
  "action_required": "none|reply|schedule|forward|archive",
  "confidence": 0.0 a 1.0
}

EMAIL A ANALIZAR:
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
De: {$email->from_email} ({$email->from_name})
Para: {$email->to_email}
Asunto: {$email->subject}

Contenido:
{$content}
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

IMPORTANTE:
- Responde SOLO con el JSON, sin markdown, sin explicaciones
- El summary debe ser en español y muy conciso
- Si detectas urgencia, usa sentiment_label: "urgent"
- Los topics deben ser palabras clave relevantes en español
PROMPT;
    }

    /**
     * Prompt del sistema (personalidad de Vera)
     *
     * @return string
     */
    protected function getSystemPrompt(): string
    {
        $personality = config('vera.personality');

        return <<<SYSTEM
Eres {$personality['name']}, {$personality['role']}.

Características:
- Tono: {$personality['tone']}
- Idioma: {$personality['language']} (español)
- Eres precisa y concisa
- Entiendes contexto y emociones
- Detectas prioridades y urgencias

Tu tarea:
Analizar emails y proporcionar insights útiles en formato JSON.
Siempre respondes en JSON válido, sin markdown ni explicaciones adicionales.
SYSTEM;
    }

    /**
     * Obtener contenido del email (limitado para ahorrar tokens)
     *
     * @param Email $email
     * @return string
     */
    protected function getEmailContent(Email $email): string
    {
        // Preferir texto plano, sino HTML sin tags
        $content = $email->body_text ?? strip_tags($email->body_html ?? '');

        // Limpiar saltos de línea excesivos
        $content = preg_replace('/\n{3,}/', "\n\n", $content);

        // Limitar a 2000 caracteres (ahorro de tokens)
        if (strlen($content) > 2000) {
            $content = substr($content, 0, 2000) . '...';
        }

        return trim($content);
    }

    /**
     * Parsear respuesta de la IA
     *
     * @param string $content
     * @return array
     */
    protected function parseAIResponse(string $content): array
    {
        try {
            // Limpiar markdown si existe
            $content = preg_replace('/```json\s*|\s*```/', '', $content);
            $content = trim($content);

            // Decodificar JSON
            $data = json_decode($content, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('JSON inválido: ' . json_last_error_msg());
            }

            // Validar y normalizar datos
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
            Log::warning('⚠️  Error parseando respuesta de IA', [
                'error' => $e->getMessage(),
                'content' => $content
            ]);

            // Retornar análisis vacío
            return $this->getEmptyAnalysis();
        }
    }

    /**
     * Análisis vacío (fallback)
     *
     * @return array
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
            'method' => 'none',
        ];
    }

    /**                                                                                                                                                             
     * Guardar análisis en el email                                                                                                                                 
     *                                                                                                                                                              
     * @param Email $email                                                                                                                                          
     * @param array $analysis                                                                                                                                       
     * @return void                                                                                                                                                 
     */
    public function saveAnalysisToEmail(Email $email, array $analysis): void
    {
        $email->update([
            'vera_summary' => $analysis['summary'],
            'vera_sentiment_score' => $analysis['sentiment_score'],
            'vera_tags' => $analysis['topics'],
            'vera_action_required' => $analysis['action_required'],
            'category' => $analysis['category'] ?? $email->category, // 🆕 Guardar categoría de Vera                                                                    
            'vera_processed' => true,
            'vera_processed_at' => now(),
        ]);

        // Actualizar prioridad si es alta o urgente                                                                                                                
        if (in_array($analysis['priority'], ['urgent', 'high'])) {
            $email->update(['priority' => $analysis['priority']]);
        }

        // 🆕 NUEVO: Aplicar workflow_status automáticamente si Vera lo sugiere                                                                                     
        if (isset($analysis['suggested_workflow_status']) && !empty($analysis['suggested_workflow_status'])) {
            $suggestedStatus = $analysis['suggested_workflow_status'];

            // Solo aplicar si el email está en estado 'unread' (no sobrescribir cambios manuales)                                                                  
            if (!$email->workflow_status || $email->workflow_status === 'unread') {
                $email->setWorkflowStatus($suggestedStatus);

                Log::info('🤖 Vera aplicó workflow_status automático', [
                    'email_id' => $email->id,
                    'workflow_status' => $suggestedStatus,
                    'reason' => "Basado en análisis: priority={$analysis['priority']}, action={$analysis['action_required']}"
                ]);
            }
        }

        Log::info('💾 Análisis guardado en email', [
            'email_id' => $email->id,
            'method' => $analysis['method'] ?? 'unknown',
            'summary' => $analysis['summary'],
            'workflow_status' => $email->workflow_status ?? 'none'
        ]);
    }

    /**
     * Test de conexión con el provider de IA
     *
     * @return array
     */
    public function testConnection(): array
    {
        if (!$this->isEnabled()) {
            return [
                'success' => false,
                'message' => 'Vera está deshabilitada',
                'enabled' => false
            ];
        }

        if (!$this->geminiProvider || !$this->geminiProvider->isConfigured()) {
            return [
                'success' => false,
                'message' => 'Gemini no está configurado',
                'configured' => false
            ];
        }

        return $this->geminiProvider->testConnection();
    }

    /**
     * Obtener estadísticas del servicio
     *
     * @return array
     */
    public function getStats(): array
    {
        return [
            'vera_enabled' => $this->isEnabled(),
            'ai_provider' => $this->aiProvider,
            'rules_service' => $this->rulesService->getStats(),
            'gemini_configured' => $this->geminiProvider
                ? $this->geminiProvider->isConfigured()
                : false,
            'gemini_info' => $this->geminiProvider
                ? $this->geminiProvider->getInfo()
                : null,
        ];
    }
}
