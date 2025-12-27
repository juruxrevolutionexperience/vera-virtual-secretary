<?php

namespace App\Services\AI;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;
use App\Events\VeraTokensConsumed;

/**
 * GeminiProvider
 *
 * Integración con Google Gemini API usando Guzzle
 */
class GeminiProvider
{
    protected Client $client;
    protected string $apiKey;
    protected string $model;
    protected string $baseUrl = 'https://generativelanguage.googleapis.com/v1beta';

    public function __construct()
    {
        $this->apiKey = config('vera.gemini_key');
        $this->model = config('vera.gemini_model', 'gemini-1.5-flash');

        $this->client = new Client([
            'base_uri' => $this->baseUrl . '/',
            'timeout' => 30,
            'headers' => [
                'Content-Type' => 'application/json',
            ]
        ]);
    }

    /**
     * Generar contenido con Gemini
     *
     * @param string $prompt
     * @param array $options
     * @return array
     * @throws \Exception
     */
    public function generateContent(string $prompt, array $options = []): array
    {
        try {
            Log::info('🤖 Llamando a Gemini API', [
                'model' => $this->model,
                'prompt_length' => strlen($prompt)
            ]);

            $endpoint = "models/{$this->model}:generateContent";

            $payload = [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $prompt]
                        ]
                    ]
                ],
                'generationConfig' => [
                    'temperature' => $options['temperature'] ?? 0.7,
                    'maxOutputTokens' => $options['maxOutputTokens'] ?? 2000,
                ]
            ];

            $response = $this->client->post($endpoint, [
                'query' => ['key' => $this->apiKey],
                'json' => $payload
            ]);

            $body = json_decode($response->getBody()->getContents(), true);

            if (!isset($body['candidates'][0]['content']['parts'][0]['text'])) {
                throw new \Exception('Respuesta de Gemini no válida: ' . json_encode($body));
            }

            $generatedText = $body['candidates'][0]['content']['parts'][0]['text'];

            Log::info('✅ Gemini respondió correctamente', [
                'response_length' => strlen($generatedText)
            ]);

            return [
                'text' => $generatedText,
                'raw_response' => $body,
                'model' => $this->model,
                'usage' => [
                    'prompt_tokens' => $body['usageMetadata']['promptTokenCount'] ?? 0,
                    'completion_tokens' => $body['usageMetadata']['candidatesTokenCount'] ?? 0,
                    'total_tokens' => $body['usageMetadata']['totalTokenCount'] ?? 0,
                ]
            ];

        } catch (GuzzleException $e) {
            Log::error('❌ Error en Gemini API (Guzzle)', [
                'error' => $e->getMessage(),
                'code' => $e->getCode()
            ]);

            throw new \Exception('Error conectando con Gemini: ' . $e->getMessage());
        } catch (\Exception $e) {
            Log::error('❌ Error procesando respuesta de Gemini', [
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    /**
     * Analizar email con Gemini
     *
     * @param string $systemPrompt
     * @param string $userPrompt
     * @param int|null $userId ID del usuario (para registrar tokens)
     * @param int|null $emailId ID del email (para registrar tokens)
     * @param string $actionType Tipo de acción ('email_analysis', 'response_generation', etc.)
     * @param array $metadata Metadata adicional para analytics
     * @return array
     */
    public function analyze(
        string $systemPrompt,
        string $userPrompt,
        ?int $userId = null,
        ?int $emailId = null,
        string $actionType = 'email_analysis',
        array $metadata = []
    ): array {
        // Gemini no tiene "system" messages separados,
        // así que combinamos ambos prompts
        $fullPrompt = $systemPrompt . "\n\n" . $userPrompt;

        try {
            $response = $this->generateContent($fullPrompt, [
                'temperature' => 0.7,
                'maxOutputTokens' => 2048
            ]);

            // 🎯 Registrar uso de tokens automáticamente
            if ($userId) {
                event(new VeraTokensConsumed(
                    userId: $userId,
                    actionType: $actionType,
                    tokenData: [
                        'prompt_tokens' => $response['usage']['prompt_tokens'] ?? 0,
                        'completion_tokens' => $response['usage']['completion_tokens'] ?? 0,
                        'total_tokens' => $response['usage']['total_tokens'] ?? 0,
                        'model' => $this->model,
                    ],
                    emailId: $emailId,
                    metadata: $metadata
                ));
            }

            return [
                'content' => $response['text'],
                'tokens_used' => $response['usage']['total_tokens'] ?? 0,
                'model' => $this->model,
                'provider' => 'gemini'
            ];

        } catch (\Exception $e) {
            Log::error('❌ Error analizando con Gemini', [
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    /**
     * Verificar si el provider está configurado correctamente
     *
     * @return bool
     */
    public function isConfigured(): bool
    {
        return !empty($this->apiKey) && !empty($this->model);
    }

    /**
     * Test de conexión
     *
     * @return array
     */
    public function testConnection(): array
    {
        try {
            $response = $this->generateContent('Responde solo "OK" si me entiendes.');

            return [
                'success' => true,
                'message' => 'Conexión exitosa con Gemini',
                'model' => $this->model,
                'response' => $response['text']
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error conectando con Gemini',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Obtener información del provider
     *
     * @return array
     */
    public function getInfo(): array
    {
        return [
            'provider' => 'gemini',
            'model' => $this->model,
            'api_key_configured' => !empty($this->apiKey),
            'api_key_preview' => !empty($this->apiKey)
                ? substr($this->apiKey, 0, 10) . '...'
                : 'No configurada',
            'base_url' => $this->baseUrl,
            'is_configured' => $this->isConfigured(),
            'cost_per_1k_tokens' => 0.0, // Gratis hasta 1,500 requests/día
            'free_tier' => '1,500 requests/día'
        ];
    }
}
