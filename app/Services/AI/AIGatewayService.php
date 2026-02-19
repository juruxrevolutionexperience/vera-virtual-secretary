<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Log;

/**
 * AIGatewayService
 *
 * Proxy abstracto sobre los providers de IA.
 * Hoy usa Gemini. Mañana, cambiando VERA_AI_PROVIDER en .env,
 * usará Claude o GPT sin tocar ningún otro archivo.
 */
class AIGatewayService
{
    protected GeminiProvider $provider;

    public function __construct()
    {
        $providerName = config('vera.ai_provider', 'gemini');

        $this->provider = match ($providerName) {
            'gemini' => new GeminiProvider(),
            default  => new GeminiProvider(),
            // Cuando añadamos más providers:
            // 'openai'     => new OpenAIProvider(),
            // 'anthropic'  => new AnthropicProvider(),
        };

        Log::debug('🤖 AIGateway usando provider: ' . $providerName);
    }

    /**
     * Analizar con IA (análisis de emails, respuestas, chat).
     * Registra tokens automáticamente via evento VeraTokensConsumed.
     */
    public function analyze(
        string $systemPrompt,
        string $userPrompt,
        ?int $userId = null,
        ?int $emailId = null,
        string $actionType = 'email_analysis',
        array $metadata = []
    ): array {
        return $this->provider->analyze(
            systemPrompt: $systemPrompt,
            userPrompt: $userPrompt,
            userId: $userId,
            emailId: $emailId,
            actionType: $actionType,
            metadata: $metadata
        );
    }

    /**
     * Generar contenido libre con IA.
     */
    public function generateContent(string $prompt, array $options = []): array
    {
        return $this->provider->generateContent($prompt, $options);
    }

    /**
     * Test de conexión con el provider activo.
     */
    public function testConnection(): array
    {
        return $this->provider->testConnection();
    }

    /**
     * Info del provider activo.
     */
    public function getProviderInfo(): array
    {
        return $this->provider->getInfo();
    }

    /**
     * Nombre del provider activo.
     */
    public function getProviderName(): string
    {
        return config('vera.ai_provider', 'gemini');
    }
}
