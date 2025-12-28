<?php

namespace App\Services;

use App\Models\User;
use App\Models\VeraConversation;
use App\Models\VeraConversationMessage;
use App\Models\VeraConfig;
use App\Models\VeraRule;
use App\Models\Email;
use App\Services\AI\GeminiProvider;
use Illuminate\Support\Facades\Log;

/**
 * VeraChatService
 *
 * FASE 3: Chat interactivo con Vera
 * - Conversaciones naturales
 * - Aprendizaje de ejemplos
 * - Creación de reglas vía chat
 * - Análisis de estilo de escritura
 */
class VeraChatService
{
    protected GeminiProvider $gemini;
    protected VeraActivityService $activityService;

    public function __construct()
    {
        $this->gemini = new GeminiProvider();
        $this->activityService = new VeraActivityService();
    }

    /**
     * Crear nueva conversación
     */
    public function createConversation(
        User $user,
        string $type = 'chat',
        ?string $title = null
    ): VeraConversation {
        return VeraConversation::create([
            'user_id' => $user->id,
            'conversation_type' => $type,
            'title' => $title,
            'status' => 'active',
        ]);
    }

    /**
     * Obtener o crear conversación activa del usuario
     */
    public function getOrCreateActiveConversation(User $user, string $type = 'chat'): VeraConversation
    {
        $conversation = VeraConversation::byUser($user->id)
            ->byType($type)
            ->active()
            ->latest()
            ->first();

        if (!$conversation) {
            $conversation = $this->createConversation($user, $type);
        }

        return $conversation;
    }

    /**
     * Enviar mensaje y obtener respuesta de Vera
     */
    public function sendMessage(
        VeraConversation $conversation,
        string $userMessage,
        array $context = []
    ): VeraConversationMessage {
        // 1. Guardar mensaje del usuario
        $userMsg = $conversation->addMessage('user', $userMessage);

        // 2. Obtener configuración del usuario
        $user = $conversation->user;
        $config = VeraConfig::where('user_id', $user->id)->first();

        // 3. Detectar intención del usuario
        $intent = $this->detectIntent($userMessage);

        // 4. Construir contexto completo de la conversación
        $conversationHistory = $this->buildConversationHistory($conversation);

        // 5. Generar respuesta según el tipo de conversación e intención
        try {
            $systemPrompt = $this->buildChatSystemPrompt($conversation, $config, $intent);
            $userPrompt = $this->buildChatUserPrompt($userMessage, $conversationHistory, $context);

            // Llamar a Gemini con registro de tokens
            $response = $this->gemini->analyze(
                systemPrompt: $systemPrompt,
                userPrompt: $userPrompt,
                userId: $user->id,
                emailId: null,
                actionType: 'chat_interaction',
                metadata: [
                    'conversation_id' => $conversation->id,
                    'conversation_type' => $conversation->conversation_type,
                    'intent' => $intent,
                    'message_length' => strlen($userMessage),
                ]
            );

            // 6. Procesar respuesta según intención detectada
            $processedResponse = $this->processResponseByIntent(
                $response['content'],
                $intent,
                $user,
                $conversation,
                $userMessage
            );

            // 7. Guardar respuesta de Vera
            $assistantMsg = $conversation->messages()->create([
                'role' => 'assistant',
                'content' => $processedResponse['content'],
                'tokens_consumed' => $response['tokens_used'] ?? 0,
                'cost' => $this->calculateCost($response['tokens_used'] ?? 0),
                'metadata' => [
                    'intent' => $intent,
                    'actions_taken' => $processedResponse['actions_taken'] ?? [],
                    'model' => $response['model'] ?? 'unknown',
                ],
            ]);

            // 8. Generar título si es el primer mensaje
            if ($conversation->messages()->count() <= 2 && !$conversation->title) {
                $conversation->generateTitle();
            }

            // 9. Registrar actividad
            $this->activityService->logChatInteraction($user, $userMessage, null);

            Log::info('✅ Mensaje de chat procesado', [
                'conversation_id' => $conversation->id,
                'intent' => $intent,
                'tokens' => $response['tokens_used'] ?? 0,
            ]);

            return $assistantMsg;

        } catch (\Exception $e) {
            Log::error('❌ Error procesando mensaje de chat', [
                'error' => $e->getMessage(),
                'conversation_id' => $conversation->id,
            ]);

            // Respuesta de error
            return $conversation->addMessage(
                'assistant',
                'Lo siento, tuve un problema procesando tu mensaje. ¿Podrías intentar reformularlo?'
            );
        }
    }

    /**
     * Detectar intención del usuario
     */
    protected function detectIntent(string $message): string
    {
        $messageLower = strtolower($message);

        // Crear regla
        if (
            str_contains($messageLower, 'crea una regla') ||
            str_contains($messageLower, 'crear regla') ||
            str_contains($messageLower, 'nueva regla') ||
            str_contains($messageLower, 'quiero que') && str_contains($messageLower, 'siempre')
        ) {
            return 'create_rule';
        }

        // Aprender estilo
        if (
            str_contains($messageLower, 'aprende') ||
            str_contains($messageLower, 'ejemplo') ||
            str_contains($messageLower, 'así es como escribo') ||
            str_contains($messageLower, 'mi estilo')
        ) {
            return 'learn_style';
        }

        // Revisar email
        if (
            str_contains($messageLower, 'revisa el email') ||
            str_contains($messageLower, 'qué opinas de') ||
            str_contains($messageLower, 'ayúdame con este email')
        ) {
            return 'review_email';
        }

        // Análisis de actividad
        if (
            str_contains($messageLower, 'estadísticas') ||
            str_contains($messageLower, 'cuántos emails') ||
            str_contains($messageLower, 'resumen') ||
            str_contains($messageLower, 'actividad')
        ) {
            return 'analytics_query';
        }

        // Configuración
        if (
            str_contains($messageLower, 'configura') ||
            str_contains($messageLower, 'cambia') && (str_contains($messageLower, 'configuración') || str_contains($messageLower, 'ajuste'))
        ) {
            return 'configuration';
        }

        // Chat general
        return 'general_chat';
    }

    /**
     * Construir historial de conversación
     */
    protected function buildConversationHistory(VeraConversation $conversation, int $limit = 10): array
    {
        $messages = $conversation->messages()
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->reverse()
            ->map(function ($msg) {
                return [
                    'role' => $msg->role,
                    'content' => $msg->content,
                ];
            })
            ->toArray();

        return $messages;
    }

    /**
     * Construir prompt del sistema para chat
     */
    protected function buildChatSystemPrompt(
        VeraConversation $conversation,
        ?VeraConfig $config,
        string $intent
    ): string {
        $userName = $conversation->user->name ?? 'Usuario';

        $basePrompt = <<<PROMPT
Eres Vera, una asistente de IA que ayuda a {$userName} con la gestión de emails y comunicaciones.

PERSONALIDAD:
- Amigable, profesional y servicial
- Hablas de forma clara y concisa
- Usas un tono conversacional pero profesional
- Eres proactiva sugiriendo mejoras

CAPACIDADES:
1. Crear reglas personalizadas para automatizar acciones
2. Aprender del estilo de escritura del usuario
3. Revisar y mejorar borradores de emails
4. Proporcionar estadísticas y análisis de actividad
5. Ayudar con configuración y ajustes

PROMPT;

        // Agregar instrucciones específicas según intención
        $intentInstructions = match($intent) {
            'create_rule' => <<<INTENT
TAREA ACTUAL: Ayudar a crear una regla de automatización
- Pregunta detalles necesarios: condiciones, acciones, prioridades
- Sé específica sobre qué información necesitas
- Confirma la regla antes de crearla
- Explica claramente qué hará la regla

FORMATO DE REGLA:
Cuando tengas toda la información, responde con:
[CREATE_RULE]
{
  "name": "Nombre descriptivo",
  "conditions": "Cuando...",
  "actions": "Entonces...",
  "priority": "normal/high"
}
[/CREATE_RULE]
INTENT,

            'learn_style' => <<<INTENT
TAREA ACTUAL: Aprender del estilo de escritura del usuario
- Analiza ejemplos que proporcione
- Identifica patrones: saludos, despedidas, tono, formalidad
- Confirma lo que aprendiste
- Pregunta si hay más ejemplos

Al finalizar el análisis, incluye:
[LEARN_STYLE]
{
  "patterns_detected": ["patrón1", "patrón2"],
  "recommendations": "Sugerencias de mejora"
}
[/LEARN_STYLE]
INTENT,

            'review_email' => <<<INTENT
TAREA ACTUAL: Revisar y mejorar un email
- Analiza tono, claridad, ortografía
- Sugiere mejoras específicas
- Mantén el estilo del usuario
- Ofrece una versión mejorada si es necesario
INTENT,

            'analytics_query' => <<<INTENT
TAREA ACTUAL: Proporcionar información sobre actividad y estadísticas
- Usa datos relevantes que tengas disponibles
- Sé específica con números y métricas
- Ofrece insights útiles
INTENT,

            default => <<<INTENT
TAREA ACTUAL: Conversación general
- Responde de forma natural y útil
- Ofrece ayuda proactivamente
- Sugiere acciones relevantes
INTENT,
        };

        return $basePrompt . "\n\n" . $intentInstructions;
    }

    /**
     * Construir prompt del usuario para chat
     */
    protected function buildChatUserPrompt(
        string $userMessage,
        array $conversationHistory,
        array $context = []
    ): string {
        $historyText = '';
        if (!empty($conversationHistory)) {
            $historyText = "HISTORIAL DE CONVERSACIÓN:\n";
            foreach ($conversationHistory as $msg) {
                $role = $msg['role'] === 'user' ? 'Usuario' : 'Vera';
                $historyText .= "{$role}: {$msg['content']}\n";
            }
            $historyText .= "\n";
        }

        $contextText = '';
        if (!empty($context)) {
            $contextText = "CONTEXTO ADICIONAL:\n" . json_encode($context, JSON_PRETTY_PRINT) . "\n\n";
        }

        return <<<PROMPT
{$historyText}{$contextText}MENSAJE ACTUAL DEL USUARIO:
{$userMessage}
PROMPT;
    }

    /**
     * Procesar respuesta según intención detectada
     */
    protected function processResponseByIntent(
        string $response,
        string $intent,
        User $user,
        VeraConversation $conversation,
        string $originalMessage
    ): array {
        $actions = [];

        // Detectar y ejecutar acciones especiales
        switch ($intent) {
            case 'create_rule':
                $ruleData = $this->extractRuleData($response);
                if ($ruleData) {
                    $rule = $this->createRuleFromChat($user, $ruleData);
                    $actions[] = [
                        'type' => 'rule_created',
                        'rule_id' => $rule->id,
                        'rule_name' => $rule->name,
                    ];
                }
                break;

            case 'learn_style':
                $styleData = $this->extractStyleData($response);
                if ($styleData) {
                    $this->updateWritingStyle($user, $styleData, $originalMessage);
                    $actions[] = [
                        'type' => 'style_learned',
                        'patterns' => $styleData['patterns_detected'] ?? [],
                    ];
                }
                break;
        }

        // Limpiar marcadores de la respuesta final
        $cleanResponse = preg_replace('/\[CREATE_RULE\].*?\[\/CREATE_RULE\]/s', '', $response);
        $cleanResponse = preg_replace('/\[LEARN_STYLE\].*?\[\/LEARN_STYLE\]/s', '', $cleanResponse);
        $cleanResponse = trim($cleanResponse);

        return [
            'content' => $cleanResponse,
            'actions_taken' => $actions,
        ];
    }

    /**
     * Extraer datos de regla desde la respuesta
     */
    protected function extractRuleData(string $response): ?array
    {
        if (preg_match('/\[CREATE_RULE\](.*?)\[\/CREATE_RULE\]/s', $response, $matches)) {
            try {
                return json_decode($matches[1], true);
            } catch (\Exception $e) {
                Log::warning('Error parseando regla desde chat', ['error' => $e->getMessage()]);
            }
        }
        return null;
    }

    /**
     * Extraer datos de estilo desde la respuesta
     */
    protected function extractStyleData(string $response): ?array
    {
        if (preg_match('/\[LEARN_STYLE\](.*?)\[\/LEARN_STYLE\]/s', $response, $matches)) {
            try {
                return json_decode($matches[1], true);
            } catch (\Exception $e) {
                Log::warning('Error parseando estilo desde chat', ['error' => $e->getMessage()]);
            }
        }
        return null;
    }

    /**
     * Crear regla desde chat
     */
    protected function createRuleFromChat(User $user, array $ruleData): VeraRule
    {
        return VeraRule::create([
            'user_id' => $user->id,
            'name' => $ruleData['name'] ?? 'Regla sin nombre',
            'description' => $ruleData['conditions'] ?? '',
            'conditions' => [
                'type' => 'all',
                'rules' => [], // Simplificado por ahora
            ],
            'actions' => [
                [
                    'action' => 'custom',
                    'description' => $ruleData['actions'] ?? '',
                ]
            ],
            'priority' => $ruleData['priority'] ?? 'normal',
            'is_active' => true,
            'created_via' => 'chat',
        ]);
    }

    /**
     * Actualizar estilo de escritura del usuario
     */
    protected function updateWritingStyle(User $user, array $styleData, string $example): void
    {
        $config = VeraConfig::where('user_id', $user->id)->first();

        if (!$config) {
            return;
        }

        $writingStyle = $config->writing_style ?? [];

        // Agregar ejemplo a los patrones aprendidos
        if (!isset($writingStyle['learned_examples'])) {
            $writingStyle['learned_examples'] = [];
        }

        $writingStyle['learned_examples'][] = [
            'text' => substr($example, 0, 500),
            'learned_at' => now()->toDateTimeString(),
            'patterns' => $styleData['patterns_detected'] ?? [],
        ];

        // Limitar a los últimos 10 ejemplos
        $writingStyle['learned_examples'] = array_slice($writingStyle['learned_examples'], -10);

        $config->update(['writing_style' => $writingStyle]);

        Log::info('✅ Estilo de escritura actualizado', [
            'user_id' => $user->id,
            'patterns' => count($styleData['patterns_detected'] ?? []),
        ]);
    }

    /**
     * Calcular costo basado en tokens (Gemini es gratis hasta 1500 req/día)
     */
    protected function calculateCost(int $tokens): float
    {
        // Gemini es gratis en el tier gratuito, pero podemos estimar
        return 0.0;
    }

    /**
     * Obtener conversaciones del usuario
     */
    public function getUserConversations(User $user, int $limit = 20)
    {
        return VeraConversation::byUser($user->id)
            ->with(['messages' => function($q) {
                $q->latest()->limit(1);
            }])
            ->active()
            ->orderByDesc('updated_at')
            ->limit($limit)
            ->get();
    }

    /**
     * Archivar conversación
     */
    public function archiveConversation(VeraConversation $conversation): void
    {
        $conversation->archive();
    }
}
