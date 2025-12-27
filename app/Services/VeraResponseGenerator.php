<?php

namespace App\Services;

use App\Models\Email;
use App\Models\VeraConfig;
use App\Models\VeraEmailAction;
use App\Models\CommunicationProfile;
use App\Services\AI\GeminiProvider;
use App\Services\VeraUsageService;
use Illuminate\Support\Facades\Log;

/**
 * VeraResponseGenerator
 *
 * Genera respuestas automáticas inteligentes que imitan el estilo del usuario
 */
class VeraResponseGenerator
{
    protected GeminiProvider $gemini;
    protected VeraUsageService $usageService;
    protected VeraActivityService $activityService;

    public function __construct()
    {
        $this->gemini = new GeminiProvider();
        $this->usageService = new VeraUsageService();
        $this->activityService = new VeraActivityService();
    }

    /**
     * Generar respuesta automática para un email (CON AUTO-ENVÍO - Nivel 2)
     */
    public function generateResponse(Email $email): ?VeraEmailAction
    {
        Log::info('🤖 Intentando generar respuesta automática', [
            'email_id' => $email->id,
            'from' => $email->from_email,
            'subject' => $email->subject,
        ]);

        // 1. Verificar que el email necesita respuesta
        if (!$this->needsResponse($email)) {
            Log::info('⏭️  Email no necesita respuesta', ['email_id' => $email->id]);
            return null;
        }

        // 2. Obtener configuración de Vera
        $config = VeraConfig::where('user_id', $email->user_id)->first();

        if (!$config || !$config->auto_response_enabled) {
            Log::info('⏸️  Respuestas automáticas deshabilitadas', ['email_id' => $email->id]);
            return null;
        }

        // 3. Verificar límites del usuario (NUEVO)
        $user = $email->user;
        if (!$this->usageService->canProcessEmail($user)) {
            Log::warning('🚫 Usuario sin límite disponible, no se genera respuesta', [
                'email_id' => $email->id,
                'user_id' => $user->id,
                'plan' => $user->plan_type,
                'emails_used' => $this->usageService->getEmailsProcessedThisMonth($user),
                'limit' => $user->monthly_email_limit,
            ]);
            return null;
        }

        // 4. Verificar horario de disponibilidad
        if (!$config->isWithinAvailabilityHours()) {
            Log::info('⏰ Fuera de horario de disponibilidad', [
                'email_id' => $email->id,
                'now' => now()->format('H:i'),
                'availability' => $config->availability_start . ' - ' . $config->availability_end,
            ]);
            return null;
        }

        // 5. Verificar si ya existe una respuesta generada
        $existingResponse = VeraEmailAction::where('email_id', $email->id)
            ->where('action_type', 'replied')
            ->first();

        if ($existingResponse) {
            Log::info('ℹ️  Ya existe una respuesta generada', ['email_id' => $email->id]);
            return $existingResponse;
        }

        // 6. Obtener perfil de comunicación con el remitente
        $profile = CommunicationProfile::where('user_id', $email->user_id)
            ->where('email_address', $email->from_email)
            ->first();

        // 7. Obtener contexto (thread, emails previos)
        $context = $this->getConversationContext($email);

        // 8. Generar respuesta con IA
        try {
            $response = $this->generateWithAI($email, $config, $profile, $context);

            // Registrar email como procesado (NUEVO)
            $this->usageService->recordEmailProcessing($user, $email, 'response_generation');

            // 9. Guardar en vera_email_actions
            $action = VeraEmailAction::create([
                'email_id' => $email->id,
                'user_id' => $email->user_id,
                'action_type' => 'replied',
                'action_description' => 'Respuesta generada automáticamente por Vera',
                'generated_response' => $response['content'],
                'reasoning' => $response['reasoning'],
                'confidence_score' => $response['confidence'],
                'user_feedback' => 'pending',
                'response_sent' => false,
            ]);

            Log::info('✅ Respuesta generada exitosamente', [
                'email_id' => $email->id,
                'action_id' => $action->id,
                'confidence' => $response['confidence'],
                'emails_remaining' => $this->usageService->getRemainingEmails($user),
            ]);

            // Registrar actividad de respuesta generada
            $tokenUsage = \App\Models\VeraTokenUsage::where('email_id', $email->id)
                ->where('action_type', 'response_generation')
                ->latest()
                ->first();

            $this->activityService->logEmailReplied($email, $action, $tokenUsage, false);

            // 10. AUTO-APROBACIÓN Y AUTO-ENVÍO según configuración
            $this->processAutoApproval($action, $config, $email);

            return $action;

        } catch (\Exception $e) {
            Log::error('❌ Error generando respuesta', [
                'email_id' => $email->id,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Procesar auto-aprobación y envío según configuración (NUEVO - Nivel 2)
     */
    protected function processAutoApproval(VeraEmailAction $action, VeraConfig $config, Email $email): void
    {
        // Si requiere aprobación manual → NO enviar automáticamente
        if ($config->auto_response_require_approval) {
            Log::info('⏸️  Requiere aprobación manual, NO se envía automáticamente', [
                'action_id' => $action->id,
            ]);
            return;
        }

        // Verificar umbral de confianza
        if ($action->confidence_score < $config->confidence_threshold) {
            Log::warning('⚠️  Confianza insuficiente, NO se envía automáticamente', [
                'action_id' => $action->id,
                'confidence' => $action->confidence_score,
                'threshold' => $config->confidence_threshold,
            ]);

            $action->update([
                'user_feedback' => 'rejected',
                'user_notes' => 'Auto-rechazada: confianza insuficiente',
            ]);

            return;
        }

        // AUTO-APROBAR
        $action->update([
            'user_feedback' => 'approved',
            'user_notes' => 'Auto-aprobada por Vera (confianza: ' . round($action->confidence_score * 100) . '%)',
        ]);

        Log::info('✅ Respuesta AUTO-APROBADA', [
            'action_id' => $action->id,
            'confidence' => $action->confidence_score,
        ]);

        // AUTO-ENVIAR
        $this->sendApprovedResponse($action, $email);
    }

    /**
     * Enviar respuesta aprobada automáticamente (NUEVO - Nivel 2)
     */
    protected function sendApprovedResponse(VeraEmailAction $action, Email $email): void
    {
        try {
            // Obtener cuenta de email del usuario
            $emailAccount = \App\Models\EmailAccount::where('user_id', $email->user_id)
                ->where('provider', $email->provider)
                ->where('sync_enabled', true)
                ->first();

            if (!$emailAccount) {
                Log::error('❌ No hay cuenta de email activa', ['user_id' => $email->user_id]);
                return;
            }

            // Preparar datos del email
            $emailData = [
                'from' => $emailAccount->email_address,
                'to' => $email->from_email,
                'subject' => 'Re: ' . $email->subject,
                'body' => $action->generated_response,
                'in_reply_to' => $email->provider_message_id,
                'references' => $email->provider_message_id,
                'thread_id' => $email->provider_thread_id,
            ];

            // Enviar según proveedor
            $gmailService = new \App\Services\GmailSyncService();
            $sentEmail = $gmailService->sendEmail($emailAccount, $emailData);

            if ($sentEmail) {
                // Marcar como enviada
                $action->update([
                    'response_sent' => true,
                    'response_sent_at' => now(),
                ]);

                // Marcar email original como respondido
                $email->update([
                    'replied_at' => now(),
                ]);

                // Actualizar actividad para reflejar que fue enviada
                $activity = \App\Models\VeraActivityLog::where('email_id', $email->id)
                    ->where('activity_type', 'email_replied')
                    ->latest()
                    ->first();

                if ($activity) {
                    $metadata = $activity->metadata ?? [];
                    $metadata['response_sent'] = true;
                    $metadata['auto_sent'] = true;
                    $activity->update([
                        'metadata' => $metadata,
                        'description' => $activity->description . ' (Enviada automáticamente)',
                    ]);
                }

                Log::info('📧 ✅ Respuesta ENVIADA automáticamente', [
                    'action_id' => $action->id,
                    'email_id' => $email->id,
                    'sent_email_id' => $sentEmail->id,
                ]);
            } else {
                Log::error('❌ Error enviando respuesta', ['action_id' => $action->id]);
            }

        } catch (\Exception $e) {
            Log::error('❌ Excepción enviando respuesta', [
                'action_id' => $action->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Verificar si el email necesita respuesta (SIMPLIFICADO - MVP)
     */
    protected function needsResponse(Email $email): bool
    {
        // 1. Ya está archivado → NO responder
        if ($email->is_archived) {
            Log::debug('❌ Email archivado, no responder', ['email_id' => $email->id]);
            return false;
        }

        // 2. Obtener configuración del usuario
        $config = VeraConfig::where('user_id', $email->user_id)->first();

        if (!$config || !$config->auto_response_enabled) {
            Log::debug('❌ Auto-respuestas deshabilitadas', ['email_id' => $email->id]);
            return false;
        }

        // 3. Análisis de contenido
        $content = strtolower($email->body_text ?? strip_tags($email->body_html ?? ''));

        if (empty(trim($content))) {
            Log::debug('❌ Sin contenido', ['email_id' => $email->id]);
            return false;
        }

        // 4. Detectar emails automáticos/newsletters (NUNCA responder)
        $isAutomated = str_contains($content, 'no-reply')
                    || str_contains($content, 'unsubscribe')
                    || str_contains($email->from_email, 'noreply')
                    || str_contains($email->from_email, 'no-reply')
                    || $email->category === 'promotions'
                    || $email->category === 'updates';

        if ($isAutomated) {
            Log::debug('❌ Email automático/newsletter', ['email_id' => $email->id]);
            return false;
        }

        // 5. Detectar agradecimientos simples (NO responder)
        // Solo considerar agradecimiento simple si:
        // - Es MUY corto (< 50 chars)
        // - Contiene "gracias" o "thank"
        // - NO tiene preguntas ni solicitudes evidentes
        $isVeryShort = strlen($content) < 50;
        $hasThankYou = str_contains($content, 'gracias') || str_contains($content, 'thank');
        $hasNoQuestions = !preg_match('/\?/', $content)
                       && !str_contains($content, 'interesado')
                       && !str_contains($content, 'información')
                       && !str_contains($content, 'consulta');

        $isThankYou = $hasThankYou && $isVeryShort && $hasNoQuestions;

        if ($isThankYou) {
            Log::debug('❌ Solo agradecimiento', ['email_id' => $email->id]);
            return false;
        }

        // 6. Detectar indicadores de necesidad de respuesta
        $hasQuestions = preg_match('/\?/', $content)
                     || str_contains($content, 'puedes')
                     || str_contains($content, 'podrías')
                     || str_contains($content, 'me gustaría saber')
                     || str_contains($content, 'información')
                     || str_contains($content, 'interesado')
                     || str_contains($content, 'consulta');

        $hasRequests = str_contains($content, 'necesito')
                    || str_contains($content, 'requiero')
                    || str_contains($content, 'por favor')
                    || str_contains($content, 'ayuda');

        $isHighPriority = in_array($email->priority, ['high', 'urgent'])
                       || $email->vera_action_required === 'reply';

        // 7. Decisión según modo configurado (SIMPLIFICADO)
        $shouldRespond = match ($config->auto_response_mode) {
            // ALWAYS: Responder a casi todos excepto automáticos/agradecimientos
            'always' => true,

            // SUGGEST_RESPONSES: Igual que always (diferencia está en require_approval)
            'suggest_responses' => true,

            // IMPORTANT_ONLY: Solo preguntas, solicitudes o alta prioridad
            'important_only' => $hasQuestions || $hasRequests || $isHighPriority,

            // ASK_FIRST: Solo preguntas directas
            'ask_first' => $hasQuestions,

            // FILTER_FOCUS o NO: No responder automáticamente
            'filter_focus', 'no' => false,

            // Default: conservador (solo preguntas/solicitudes)
            default => $hasQuestions || $hasRequests,
        };

        Log::info($shouldRespond ? '✅ Email NECESITA respuesta' : '❌ Email NO necesita respuesta', [
            'email_id' => $email->id,
            'mode' => $config->auto_response_mode,
            'has_questions' => $hasQuestions,
            'has_requests' => $hasRequests,
            'is_high_priority' => $isHighPriority,
        ]);

        return $shouldRespond;
    }

    /**
     * Obtener contexto de la conversación
     */
    protected function getConversationContext(Email $email)
    {
        // Buscar emails del mismo thread o con el mismo remitente
        return Email::where('user_id', $email->user_id)
            ->where(function($query) use ($email) {
                $query->where('thread_id', $email->thread_id)
                      ->orWhere('from_email', $email->from_email)
                      ->orWhere('to_email', $email->from_email);
            })
            ->where('id', '!=', $email->id)
            ->orderByDesc('received_at')
            ->limit(5)
            ->get();
    }

    /**
     * Generar respuesta usando IA
     */
    protected function generateWithAI(Email $email, VeraConfig $config, ?CommunicationProfile $profile, $context): array
    {
        if (!$this->gemini->isConfigured()) {
            throw new \Exception('Gemini no está configurado');
        }

        $systemPrompt = $this->buildSystemPrompt($config, $profile);
        $userPrompt = $this->buildUserPrompt($email, $context);

        // Llamar a Gemini con registro automático de tokens
        $response = $this->gemini->analyze(
            systemPrompt: $systemPrompt,
            userPrompt: $userPrompt,
            userId: $email->user_id,
            emailId: $email->id,
            actionType: 'response_generation',
            metadata: [
                'subject' => $email->subject,
                'from' => $email->from_email,
                'content_length' => strlen($email->body_text ?? $email->body_html ?? ''),
                'context_emails' => $context->count(),
            ]
        );

        return $this->parseResponse($response['content']);
    }

    /**
     * Construir prompt del sistema
     */
    protected function buildSystemPrompt(VeraConfig $config, ?CommunicationProfile $profile): string
    {
        $writingStyle = $config->writing_style;

        // Saludos y despedidas
        $greetings = isset($writingStyle['greetings']) && !empty($writingStyle['greetings'])
            ? implode(', ', $writingStyle['greetings'])
            : 'Hola';

        $closings = isset($writingStyle['closings']) && !empty($writingStyle['closings'])
            ? implode(', ', $writingStyle['closings'])
            : 'Saludos';

        $tone = $writingStyle['tone'] ?? 'profesional';
        $formalityLevel = $writingStyle['formality_score'] ?? 0.5;
        $usesEmojis = $writingStyle['uses_emojis'] ?? false;
        $avgLength = $writingStyle['avg_length'] ?? 500;

        // Información del perfil del remitente
        $profileInfo = '';
        if ($profile) {
            $profileInfo = <<<PROFILE

PERFIL DEL REMITENTE:
- Relación: {$profile->detected_tone}
- Frecuencia de comunicación: {$profile->communication_frequency}
- Nivel de formalidad habitual: {$profile->formality_score}
PROFILE;
        }

        return <<<PROMPT
Eres un asistente que genera respuestas de email imitando EXACTAMENTE el estilo de escritura del usuario.

ESTILO DEL USUARIO:
- Saludos típicos: {$greetings}
- Despedidas típicas: {$closings}
- Tono general: {$tone}
- Nivel de formalidad: {$formalityLevel} (0=muy casual, 1=muy formal)
- Usa emojis: {$this->formatBoolean($usesEmojis)}
- Longitud promedio de emails: {$avgLength} caracteres
{$profileInfo}

INSTRUCCIONES CRÍTICAS:
1. Genera una respuesta que suene EXACTAMENTE como el usuario escribiría
2. Usa SOLO los saludos y despedidas que el usuario usa habitualmente
3. Mantén el mismo nivel de formalidad
4. Ajusta la longitud según el tipo de email
5. Si el usuario usa emojis, úsalos tú también (pero con moderación)
6. Si el usuario NO usa emojis, NO los uses
7. Sé natural, humano y auténtico
8. NO seas genérico ni robótico

FORMATO DE RESPUESTA:
Responde SOLO con un JSON válido. IMPORTANTE: Usa \\n para saltos de línea dentro del content, NO uses saltos de línea reales en el JSON:
{
  "content": "El contenido completo de la respuesta con \\n para saltos de línea",
  "confidence": 0.75,
  "reasoning": "Por qué generaste esta respuesta"
}

El "content" debe ser el email completo listo para enviar (con saludo y despedida).
CRÍTICO: El JSON debe estar en una sola línea, usa \\n dentro de las cadenas de texto.
PROMPT;
    }

    /**
     * Construir prompt del usuario
     */
    protected function buildUserPrompt(Email $email, $context): string
    {
        $emailContent = $this->getEmailContent($email);

        $threadContext = '';
        if ($context->isNotEmpty()) {
            $threadContext = "\n\nCONTEXTO DE CONVERSACIÓN PREVIA:\n";
            foreach ($context as $i => $prevEmail) {
                $from = $prevEmail->from_email === $email->user->email ? 'Tú' : $prevEmail->from_name;
                $snippet = $prevEmail->snippet ?? substr($prevEmail->body_text ?? '', 0, 150);
                $threadContext .= "  {$from}: {$snippet}...\n";
            }
        }

        return <<<PROMPT
EMAIL A RESPONDER:
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
De: {$email->from_email} ({$email->from_name})
Asunto: {$email->subject}

{$emailContent}
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
{$threadContext}

Genera una respuesta apropiada usando el estilo del usuario.
La respuesta debe ser natural, profesional y directa al punto.
PROMPT;
    }

    /**
     * Obtener contenido del email
     */
    protected function getEmailContent(Email $email): string
    {
        $content = $email->body_text ?? strip_tags($email->body_html ?? '');

        // Limpiar saltos de línea excesivos
        $content = preg_replace('/\n{3,}/', "\n\n", $content);

        // Limitar a 1500 caracteres
        if (strlen($content) > 1500) {
            $content = substr($content, 0, 1500) . '...';
        }

        return trim($content);
    }

    /**
     * Parsear respuesta de la IA
     */
    protected function parseResponse(string $content): array
    {
        try {
            // Limpiar markdown si existe
            $content = preg_replace('/```json\s*|\s*```/', '', $content);
            $content = trim($content);

            // Log completo para debugging
            Log::debug('🔍 Parseando respuesta de IA', [
                'content_length' => strlen($content),
                'full_content' => $content
            ]);

            // Intentar decodificar directamente primero
            $data = @json_decode($content, true);

            // Si falla, intentar con multiline mode (quitando solo estructurales)
            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::debug('⚠️  Primer intento de parse falló, intentando limpiar');

                // Quitar solo los saltos de línea que están FUERA de comillas
                // Reemplazar }\n{ con }{, :\n" con :",  etc
                $cleanContent = preg_replace('/([}\]])[\s\n]+([{\[])/','$1$2', $content);
                $cleanContent = preg_replace('/,[\s\n]+/', ',', $cleanContent);
                $cleanContent = preg_replace('/:[\s\n]+"/', ':"', $cleanContent);
                $cleanContent = preg_replace('/\{[\s\n]+"/', '{"', $cleanContent);

                $data = @json_decode($cleanContent, true);
            }

            if (json_last_error() !== JSON_ERROR_NONE || !$data) {
                throw new \Exception('JSON inválido: ' . json_last_error_msg());
            }

            return [
                'content' => $data['content'] ?? '',
                'confidence' => (float) ($data['confidence'] ?? 0.5),
                'reasoning' => $data['reasoning'] ?? 'Sin razón especificada',
            ];

        } catch (\Exception $e) {
            Log::warning('⚠️  Error parseando respuesta de IA', [
                'error' => $e->getMessage(),
                'content_length' => strlen($content),
                'full_content' => $content,
                'content_preview' => substr($content, 0, 500)
            ]);

            throw $e;
        }
    }

    /**
     * Formatear booleano
     */
    protected function formatBoolean(bool $value): string
    {
        return $value ? 'Sí' : 'No';
    }
}
