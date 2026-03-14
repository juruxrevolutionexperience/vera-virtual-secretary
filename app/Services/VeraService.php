<?php

namespace App\Services;

use App\Models\Email;
use App\Services\AI\GeminiProvider;
use App\Services\AI\GroqProvider;
use App\Services\AI\AIGatewayService;
use App\Services\VeraUsageService;
use App\Services\VeraRAGService;
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
    protected VeraRAGService $ragService;
    protected AIGatewayService $aiGateway;
    protected GeminiProvider|GroqProvider|null $geminiProvider = null;
    protected string $aiProvider;

    public function __construct()
    {
        $this->rulesService = new VeraRulesService();
        $this->usageService = new VeraUsageService();
        $this->ragService   = new VeraRAGService();
        $this->aiGateway    = new AIGatewayService();
        $this->aiProvider   = config('vera.ai_provider', 'gemini');

        // Inicializar provider de IA si está habilitado
        if ($this->isEnabled()) {
            $this->geminiProvider = match ($this->aiProvider) {
                'groq'  => new GroqProvider(),
                default => new GeminiProvider(),
            };
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

            // PASO 3.5: Clasificación rápida (modelo dual barato → caro)
            // ~80 tokens en vez de ~600. Solo si Gemini está disponible.
            if ($this->geminiProvider && $this->geminiProvider->isConfigured()) {
                $bodyPreview = substr(
                    $email->body_text ?? strip_tags($email->body_html ?? ''),
                    0,
                    200
                );

                $classification = $this->geminiProvider->quickClassify(
                    subject: $email->subject ?? '',
                    bodyPreview: $bodyPreview,
                    fromEmail: $email->from_email ?? '',
                    userId: $user->id,
                    emailId: $email->id
                );

                if (!$classification['needs_full_analysis']) {
                    Log::info('⚡ quickClassify: email descartado, solo reglas', [
                        'email_id' => $email->id,
                        'motivo'   => $classification['reason'],
                        'tokens'   => $classification['tokens_used'],
                    ]);

                    return array_merge($rulesAnalysis, [
                        'method'           => 'rules_classified',
                        'classify_decision'=> 'IGNORAR',
                        'classify_reason'  => $classification['reason'],
                    ]);
                }

                Log::info('⚡ quickClassify: email aprobado para análisis completo', [
                    'email_id' => $email->id,
                    'motivo'   => $classification['reason'],
                    'tokens'   => $classification['tokens_used'],
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
            // RAG: obtener contexto relevante para este email
            $ragContext = $this->ragService->getRelevantContext($email);

            // Cargar config de Vera del usuario para personalizar el análisis
            $veraConfig = \App\Models\VeraConfig::where('user_id', $email->user_id)->first();

            // Construir prompts
            $systemPrompt = $this->getSystemPrompt($ragContext['context_text']);
            $userPrompt = $this->buildAnalysisPrompt($email, $veraConfig);

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
                'rag_chunks'     => $ragContext['chunks_used'],
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
     * @param \App\Models\VeraConfig|null $veraConfig Configuración del usuario para personalizar análisis
     * @return string
     */
    protected function buildAnalysisPrompt(Email $email, ?\App\Models\VeraConfig $veraConfig = null): string
    {
        $content = $this->getEmailContent($email);

        // Contexto de configuración del usuario
        $userConfigContext = $this->buildUserConfigContext($veraConfig);

        return <<<PROMPT
Analiza el siguiente email y devuelve un JSON con esta estructura EXACTA:

{
  "summary": "Resumen corto en español (máximo 150 caracteres)",
  "category": "promotional|newsletter|social|work|personal|transactional",
  "sentiment_score": 0.0 a 1.0 (0=muy negativo, 0.5=neutral, 1=muy positivo),
  "sentiment_label": "positive|neutral|negative|urgent",
  "priority": "low|normal|high|urgent",
  "topics": ["tema1", "tema2", "tema3"],
  "action_required": "none|reply|schedule|forward|archive",
  "confidence": 0.0 a 1.0,
  "sentimiento_tono": "neutro|amistoso|urgente|presionado|molesto|entusiasta|formal|agresivo",
  "badge_sugerido": "Trabajo|Personal|Urgente|Cliente|Proveedor|Reunión|Seguimiento|Factura|Soporte",
  "aprender_de_esto": "Una frase corta (máx 120 chars) sobre preferencias del remitente o patrón útil. null si no aplica.",
  "suggested_reply": "Borrador completo de respuesta o null"
}

{$userConfigContext}

REGLAS DE CATEGORIZACIÓN (críticas):
- "newsletter": tiene enlace para darse de baja, es un boletín periódico, bulk email
- "promotional": oferta, descuento, marketing, publicidad
- "transactional": recibo, factura, confirmación de pedido, OTP, reset de contraseña
- "social": notificación de LinkedIn, Twitter, Facebook, Instagram
- "work": email de trabajo de persona real (dominio corporativo, contexto laboral)
- "personal": email personal de persona real

REGLAS PARA action_required (MUY IMPORTANTE):
- "reply": si category es "work" o "personal" → SIEMPRE "reply". Las personas reales SIEMPRE merecen respuesta, sin excepción.
- "archive": si category es newsletter, promotional, transactional o social → SIEMPRE "archive"
- "schedule": si es work/personal Y menciona explícitamente una cita, reunión o evento concreto con fecha
- "none": SOLO para emails automáticos sin categoría clara. NUNCA para work/personal.
- NUNCA uses "reply" para newsletters, promociones o emails automáticos

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
- suggested_reply: Solo si action_required es 'reply'. Usa el estilo del usuario del contexto RAG. null en cualquier otro caso.
PROMPT;
    }

    /**
     * Construir contexto de configuración del usuario para el prompt
     */
    protected function buildUserConfigContext(?\App\Models\VeraConfig $config): string
    {
        if (!$config) {
            return '';
        }

        $mode = $config->auto_response_mode ?? 'ask_first';
        $objective = $config->main_objective ?? '';

        $modeDescription = match ($mode) {
            'always'           => 'Responder automáticamente a todos los emails de personas reales.',
            'suggest_responses'=> 'Sugerir respuestas para revisión del usuario.',
            'important_only'   => 'Responder solo a emails importantes (prioridad alta o urgente).',
            'ask_first'        => 'Solo sugerir respuesta si hay una pregunta directa.',
            'filter_focus'     => 'Solo categorizar y filtrar, NO generar respuestas.',
            'no'               => 'NO generar respuestas automáticas.',
            default            => 'Solo sugerir si hay pregunta directa.',
        };

        $noReplyModes = ['filter_focus', 'no'];
        $replyInstruction = in_array($mode, $noReplyModes)
            ? 'IMPORTANTE: suggested_reply debe ser SIEMPRE null porque el usuario no quiere respuestas automáticas.'
            : '';

        $lines = ["CONFIGURACIÓN DEL USUARIO:\n- Modo de Vera: {$modeDescription}"];

        if ($objective) {
            $lines[] = "- Objetivo principal del usuario: {$objective}";
        }

        if (!empty($config->blocked_keywords)) {
            $keywords = implode(', ', (array) $config->blocked_keywords);
            $lines[] = "- Palabras clave bloqueadas (no responder si aparecen): {$keywords}";
        }

        if ($replyInstruction) {
            $lines[] = $replyInstruction;
        }

        return implode("\n", $lines);
    }

    /**
     * Prompt del sistema (personalidad de Vera)
     *
     * @return string
     */
    protected function getSystemPrompt(string $ragContext = ''): string
    {
        $personality = config('vera.personality');

        $ragBlock = !empty($ragContext)
            ? "\n{$ragContext}\n"
            : '';

        return <<<SYSTEM
Eres {$personality['name']}, {$personality['role']}.

Características:
- Tono: {$personality['tone']}
- Idioma: {$personality['language']} (español)
- Eres precisa y concisa
- Entiendes contexto y emociones
- Detectas prioridades y urgencias
{$ragBlock}
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

        // Thread + Disclaimer stripping inteligente
        $content = $this->extractRelevantContent($content);

        // Limitar a 2000 caracteres (ahorro de tokens)
        if (strlen($content) > 2000) {
            $content = substr($content, 0, 2000) . '...';
        }

        return trim($content);
    }

    /**
     * Extrae solo la parte relevante del email:
     * - Elimina hilos de conversación anteriores (Re: Re: Re:)
     * - Elimina avisos legales y disclaimers
     * - Si el mensaje nuevo es muy corto, incluye contexto del hilo inmediato
     *   para el caso "De acuerdo, confirmado." (el contexto está en el hilo)
     */
    protected function extractRelevantContent(string $content): string
    {
        // Patrones que marcan el inicio del hilo antiguo
        $threadPatterns = [
            '/^De:\s.+/m',
            '/^From:\s.+/m',
            '/^El .{5,80} escribió:/m',
            '/^On .{5,80} wrote:/m',
            '/^-{3,}\s*(Original Message|Mensaje original)\s*-{3,}/mi',
            '/^_{5,}/m',
        ];

        $newMessage    = $content;
        $threadContext = '';

        foreach ($threadPatterns as $pattern) {
            if (preg_match($pattern, $content, $matches, PREG_OFFSET_CAPTURE)) {
                $cutPosition   = $matches[0][1];
                $newMessage    = substr($content, 0, $cutPosition);
                // Guardar el inicio del hilo como contexto potencial
                $threadContext = substr($content, $cutPosition, 600);
                break;
            }
        }

        $newMessage = trim($newMessage);

        // Edge case: mensaje nuevo muy corto → el contexto está en el hilo
        // Ejemplo: "De acuerdo, confirmado." necesita saber qué se confirmó
        // Umbral 60 chars: suficiente para respuestas cortas sin activar para mensajes normales
        if (strlen($newMessage) < 60 && $threadContext) {
            // Limpiar cabeceras del hilo: De, From, Enviado, Sent, Para, To, CC, Asunto, Subject
            $threadContextClean = preg_replace('/^(De|From|El|On|Enviado|Sent|Para|To|CC|Asunto|Subject)[^\n]+\n?/mi', '', $threadContext);
            $threadContextClean = trim(substr($threadContextClean, 0, 400));

            if ($threadContextClean) {
                $newMessage .= "\n\n[Contexto del mensaje anterior:]\n" . $threadContextClean;
            }
        }

        // Disclaimer stripping: cortar antes de los avisos legales
        // Usamos \s+ para tolerar espacios múltiples entre palabras
        $disclaimerPatterns = [
            '/Este\s+mensaje\s+(y\s+sus\s+archivos|es\s+confidencial)/i',
            '/This\s+(email|message|electronic\s+message)\s+(and\s+any|contains|is\s+confidential)/i',
            '/Please\s+consider\s+the\s+environment/i',
            '/AVISO\s+LEGAL|LEGAL\s+NOTICE|CONFIDENTIALITY\s+NOTICE/i',
            '/El\s+uso\s+no\s+autorizado\s+de\s+este/i',
        ];

        foreach ($disclaimerPatterns as $pattern) {
            if (preg_match($pattern, $newMessage)) {
                $parts = preg_split($pattern, $newMessage, 2, PREG_SPLIT_NO_EMPTY);
                if (!empty($parts[0])) {
                    $newMessage = trim($parts[0]);
                }
            }
        }

        // Fallback: si algo salió mal, devolver el contenido original
        return $newMessage ?: $content;
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
            $category       = $data['category'] ?? 'personal';
            $actionRequired = $data['action_required'] ?? 'none';

            // Safety net: personas reales (work/personal) siempre merecen respuesta
            if (in_array($category, ['work', 'personal']) && $actionRequired === 'none') {
                $actionRequired = 'reply';
                Log::info('🔧 Safety net: action_required forzado a reply para email personal/work');
            }

            return [
                'summary'          => $data['summary'] ?? '',
                'category'         => $category,
                'sentiment_score'  => (float) ($data['sentiment_score'] ?? 0.5),
                'sentiment_label'  => $data['sentiment_label'] ?? 'neutral',
                'priority'         => $data['priority'] ?? 'normal',
                'topics'           => $data['topics'] ?? [],
                'action_required'  => $actionRequired,
                'confidence'       => (float) ($data['confidence'] ?? 0.0),
                // Campos enriquecidos
                'sentimiento_tono' => $data['sentimiento_tono'] ?? 'neutro',
                'badge_sugerido'   => $data['badge_sugerido'] ?? null,
                'aprender_de_esto' => $data['aprender_de_esto'] ?? null,
                'suggested_reply'  => $data['suggested_reply'] ?? null,
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
            'summary'          => '',
            'category'         => 'personal',
            'sentiment_score'  => 0.5,
            'sentiment_label'  => 'neutral',
            'priority'         => 'normal',
            'topics'           => [],
            'action_required'  => 'none',
            'confidence'       => 0.0,
            'method'           => 'none',
            'sentimiento_tono' => 'neutro',
            'badge_sugerido'   => null,
            'aprender_de_esto' => null,
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
        $categoryToSave = $analysis['category'] ?? $email->category;

        Log::info('💾 Guardando categoría en email', [
            'email_id' => $email->id,
            'category_from_analysis' => $analysis['category'] ?? 'NO DEFINIDA',
            'category_current_email' => $email->category,
            'category_to_save' => $categoryToSave,
            'method' => $analysis['method'] ?? 'unknown'
        ]);

        $email->update([
            'vera_summary'          => $analysis['summary'],
            'vera_sentiment_score'  => $analysis['sentiment_score'],
            'vera_tags'             => $analysis['topics'],
            'vera_action_required'  => $analysis['action_required'],
            'vera_sentimiento_tono' => $analysis['sentimiento_tono'] ?? 'neutro',
            'vera_badge_sugerido'   => $analysis['badge_sugerido'] ?? null,
            'category'              => $categoryToSave,
            'vera_processed'        => true,
            'vera_processed_at'     => now(),
        ]);

        // Guardar aprender_de_esto en vera_memory_chunks si hay algo relevante
        $aprender = $analysis['aprender_de_esto'] ?? null;
        if (!empty($aprender) && $aprender !== 'null') {
            \App\Models\VeraMemoryChunk::create([
                'user_id'        => $email->user_id,
                'type'           => 'contact_profile',
                'title'          => ($email->from_name ?: $email->from_email),
                'content'        => $aprender,
                'keywords_text'  => $email->from_email . ' ' . ($email->from_name ?? ''),
                'relevance_tags' => [$email->from_email],
                'source'         => 'email_analysis',
                'is_active'      => true,
            ]);
        }

        // GAP 1: Crear borrador de respuesta si action_required = 'reply'
        if (($analysis['action_required'] ?? '') === 'reply' && !empty($analysis['suggested_reply'])) {
            $veraConfig = \App\Models\VeraConfig::where('user_id', $email->user_id)->first();

            $feedback = 'pending';
            if ($veraConfig) {
                $mode       = $veraConfig->auto_response_mode ?? 'ask_first';
                $threshold  = (float) ($veraConfig->confidence_threshold ?? 0.75);
                $confidence = (float) ($analysis['confidence'] ?? 0.0);
                $meetsThreshold = $confidence >= $threshold;

                if ($mode === 'always' && $meetsThreshold) {
                    $feedback = 'approved';
                } elseif ($mode === 'important_only' && in_array($analysis['priority'] ?? '', ['urgent', 'high']) && $meetsThreshold) {
                    $feedback = 'approved';
                }

                Log::info('🎯 Vera decision feedback', [
                    'email_id'       => $email->id,
                    'mode'           => $mode,
                    'confidence'     => $confidence,
                    'threshold'      => $threshold,
                    'meets_threshold'=> $meetsThreshold,
                    'feedback'       => $feedback,
                ]);
            }

            // Solo crear si no existe ya un action para este email (lock para evitar race condition)
            $newAction = \DB::transaction(function () use ($email, $analysis, $feedback) {
                $alreadyExists = \App\Models\VeraEmailAction::where('email_id', $email->id)
                    ->where('action_type', 'replied')
                    ->lockForUpdate()
                    ->exists();

                if ($alreadyExists) {
                    return null;
                }

                return \App\Models\VeraEmailAction::create([
                    'email_id'           => $email->id,
                    'user_id'            => $email->user_id,
                    'action_type'        => 'replied',
                    'action_description' => 'Borrador generado por Vera',
                    'generated_response' => $analysis['suggested_reply'],
                    'reasoning'          => "Sentiment: {$analysis['sentimiento_tono']} | Priority: {$analysis['priority']} | Confidence: {$analysis['confidence']}",
                    'confidence_score'   => $analysis['confidence'],
                    'user_feedback'      => $feedback,
                    'response_sent'      => false,
                ]);
            });

            if ($newAction) {
                Log::info('📝 Vera generó borrador de respuesta', [
                    'email_id'  => $email->id,
                    'user_id'   => $email->user_id,
                    'feedback'  => $feedback,
                    'mode'      => $veraConfig->auto_response_mode ?? 'ask_first',
                ]);

                // Si fue aprobado automáticamente → enviar ahora o programar con delay
                if ($feedback === 'approved') {
                    $delayMinutes = (int) ($veraConfig->response_delay_minutes ?? 0);

                    if ($delayMinutes > 0) {
                        $scheduledAt = now()->addMinutes($delayMinutes);
                        $newAction->update(['scheduled_send_at' => $scheduledAt]);
                        Log::info('⏰ Auto-envío programado con delay', [
                            'email_id'      => $email->id,
                            'action_id'     => $newAction->id,
                            'delay_minutes' => $delayMinutes,
                            'scheduled_at'  => $scheduledAt,
                        ]);
                    } else {
                        try {
                            $generator = new \App\Services\VeraResponseGenerator();
                            $generator->sendApprovedResponse($newAction, $email);
                            Log::info('🚀 Auto-envío ejecutado desde VeraService', [
                                'email_id'  => $email->id,
                                'action_id' => $newAction->id,
                            ]);
                        } catch (\Exception $e) {
                            Log::error('❌ Error en auto-envío desde VeraService', [
                                'email_id' => $email->id,
                                'error'    => $e->getMessage(),
                            ]);
                        }
                    }
                }
            }
        }

        // Actualizar prioridad si es alta o urgente
        if (in_array($analysis['priority'], ['urgent', 'high'])) {
            $email->update(['priority' => $analysis['priority']]);
        }

        // Aplicar workflow_status: la IA puede cambiar action_required a 'reply',
        // así que recalculamos SIEMPRE basándonos en el action_required final.
        $finalAction = $analysis['action_required'] ?? 'none';

        if ($finalAction === 'reply') {
            // Comprobar si ya hay una acción aprobada y enviada (modo always + auto-envío)
            $approvedAndSent = \App\Models\VeraEmailAction::where('email_id', $email->id)
                ->where('action_type', 'replied')
                ->where('user_feedback', 'approved')
                ->where('response_sent', true)
                ->exists();

            if ($approvedAndSent) {
                // Ya se envió automáticamente → completed
                $email->setWorkflowStatus('completed');
                Log::info('✅ Vera marcó email como completed (auto-enviado)', ['email_id' => $email->id]);
            } else {
                // Pendiente de aprobación manual o envío
                $email->setWorkflowStatus('pending_response');
                Log::info('🤖 Vera marcó email como pending_response (IA: action=reply)', ['email_id' => $email->id]);
            }
        } elseif (isset($analysis['suggested_workflow_status']) && !empty($analysis['suggested_workflow_status'])) {
            $suggestedStatus = $analysis['suggested_workflow_status'];

            // Solo aplicar si el email está en estado 'unread' o sin estado (no sobrescribir cambios manuales)
            if (!$email->workflow_status || $email->workflow_status === 'unread') {
                $email->setWorkflowStatus($suggestedStatus);
                Log::info('🤖 Vera aplicó workflow_status automático', [
                    'email_id' => $email->id,
                    'workflow_status' => $suggestedStatus,
                    'reason' => "Basado en análisis: priority={$analysis['priority']}, action={$finalAction}"
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
