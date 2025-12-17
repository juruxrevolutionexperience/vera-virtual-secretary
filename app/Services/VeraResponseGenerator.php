<?php

namespace App\Services;

use App\Models\Email;
use App\Models\VeraConfig;
use App\Models\VeraEmailAction;
use App\Models\CommunicationProfile;
use App\Services\AI\GeminiProvider;
use Illuminate\Support\Facades\Log;

/**
 * VeraResponseGenerator
 *
 * Genera respuestas automáticas inteligentes que imitan el estilo del usuario
 */
class VeraResponseGenerator
{
    protected GeminiProvider $gemini;

    public function __construct()
    {
        $this->gemini = new GeminiProvider();
    }

    /**
     * Generar respuesta automática para un email
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

        // 3. Verificar si ya existe una respuesta generada
        $existingResponse = VeraEmailAction::where('email_id', $email->id)
            ->where('action_type', 'replied')
            ->first();

        if ($existingResponse) {
            Log::info('ℹ️  Ya existe una respuesta generada', ['email_id' => $email->id]);
            return $existingResponse;
        }

        // 4. Obtener perfil de comunicación con el remitente
        $profile = CommunicationProfile::where('user_id', $email->user_id)
            ->where('email_address', $email->from_email)
            ->first();

        // 5. Obtener contexto (thread, emails previos)
        $context = $this->getConversationContext($email);

        // 6. Generar respuesta con IA
        try {
            $response = $this->generateWithAI($email, $config, $profile, $context);

            // 7. Guardar en vera_email_actions
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
            ]);

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
     * Verificar si el email necesita respuesta
     */
    protected function needsResponse(Email $email): bool
    {
        // Ya está archivado o marcado para no responder
        if ($email->is_archived) {
            return false;
        }

        // Vera ya determinó que requiere respuesta
        if ($email->vera_action_required === 'reply') {
            return true;
        }

        // Prioridad alta o urgente
        if (in_array($email->priority, ['high', 'urgent'])) {
            return true;
        }

        return false;
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

        $response = $this->gemini->analyze($systemPrompt, $userPrompt);

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
Responde SOLO con un JSON válido (sin markdown, sin explicaciones):
{
  "content": "El contenido completo de la respuesta",
  "confidence": 0.75,
  "reasoning": "Por qué generaste esta respuesta y qué tono/estilo usaste"
}

El "content" debe ser el email completo listo para enviar (con saludo y despedida).
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

            // Decodificar JSON
            $data = json_decode($content, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
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
                'content' => $content
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
