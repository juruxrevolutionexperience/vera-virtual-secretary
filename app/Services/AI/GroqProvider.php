<?php

namespace App\Services\AI;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Events\VeraTokensConsumed;

/**
 * GroqProvider
 *
 * Integración con Groq API (compatible con OpenAI)
 * Modelos: llama-3.3-70b-versatile (análisis), llama-3.1-8b-instant (classify)
 */
class GroqProvider
{
    protected Client $client;
    protected string $apiKey;
    protected string $model;
    protected string $fastModel;
    protected string $baseUrl = 'https://api.groq.com/openai/v1';

    public function __construct()
    {
        $this->apiKey    = config('vera.groq_key');
        $this->model     = config('vera.groq_model', 'llama-3.3-70b-versatile');
        $this->fastModel = config('vera.groq_fast_model', 'llama-3.1-8b-instant');

        $this->client = new Client([
            'base_uri' => $this->baseUrl . '/',
            'timeout'  => 30,
            'headers'  => [
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type'  => 'application/json',
            ],
        ]);
    }

    /**
     * Rate limiter: máximo 28 requests/minuto (Groq free tier = 30 RPM, margen de seguridad)
     */
    protected function enforceRateLimit(): void
    {
        $key         = 'groq_rpm_window';
        $maxRpm      = 28;
        $timestamps  = Cache::get($key, []);
        $now         = microtime(true);
        $windowStart = $now - 60;

        $timestamps = array_filter($timestamps, fn($ts) => $ts > $windowStart);

        if (count($timestamps) >= $maxRpm) {
            $oldest   = min($timestamps);
            $waitSecs = ($oldest + 60) - $now;

            if ($waitSecs > 0) {
                Log::info('⏳ Groq rate limit: esperando ' . round($waitSecs, 2) . 's', [
                    'requests_in_window' => count($timestamps),
                    'max_rpm'            => $maxRpm,
                ]);
                usleep((int) ($waitSecs * 1_000_000));
                $now         = microtime(true);
                $windowStart = $now - 60;
                $timestamps  = array_filter($timestamps, fn($ts) => $ts > $windowStart);
            }
        }

        $timestamps[] = microtime(true);
        Cache::put($key, array_values($timestamps), 120);
    }

    /**
     * Generar contenido con Groq
     */
    public function generateContent(string $prompt, array $options = []): array
    {
        $this->enforceRateLimit();

        $model = $options['fast_model'] ?? false ? $this->fastModel : $this->model;

        try {
            Log::info('🤖 Llamando a Groq API', [
                'model'         => $model,
                'prompt_length' => strlen($prompt),
            ]);

            $payload = [
                'model'       => $model,
                'messages'    => [
                    ['role' => 'user', 'content' => $prompt],
                ],
                'temperature' => $options['temperature'] ?? 0.7,
                'max_tokens'  => $options['maxOutputTokens'] ?? 2000,
            ];

            $response = $this->client->post('chat/completions', [
                'json' => $payload,
            ]);

            $body = json_decode($response->getBody()->getContents(), true);

            if (!isset($body['choices'][0]['message']['content'])) {
                throw new \Exception('Respuesta de Groq no válida: ' . json_encode($body));
            }

            $generatedText = $body['choices'][0]['message']['content'];

            Log::info('✅ Groq respondió correctamente', [
                'response_length' => strlen($generatedText),
                'model'           => $model,
            ]);

            return [
                'text'         => $generatedText,
                'raw_response' => $body,
                'model'        => $model,
                'usage'        => [
                    'prompt_tokens'     => $body['usage']['prompt_tokens'] ?? 0,
                    'completion_tokens' => $body['usage']['completion_tokens'] ?? 0,
                    'total_tokens'      => $body['usage']['total_tokens'] ?? 0,
                ],
            ];

        } catch (GuzzleException $e) {
            Log::error('❌ Error en Groq API (Guzzle)', [
                'error' => $e->getMessage(),
                'code'  => $e->getCode(),
            ]);
            throw new \Exception('Error conectando con Groq: ' . $e->getMessage());

        } catch (\Exception $e) {
            Log::error('❌ Error procesando respuesta de Groq', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Analizar email con Groq (system + user messages separados)
     */
    public function analyze(
        string $systemPrompt,
        string $userPrompt,
        ?int $userId = null,
        ?int $emailId = null,
        string $actionType = 'email_analysis',
        array $metadata = []
    ): array {
        $this->enforceRateLimit();

        try {
            Log::info('🤖 Groq analyze()', [
                'model'   => $this->model,
                'email_id'=> $emailId,
            ]);

            $payload = [
                'model'       => $this->model,
                'messages'    => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user',   'content' => $userPrompt],
                ],
                'temperature' => 0.7,
                'max_tokens'  => 2048,
            ];

            $response = $this->client->post('chat/completions', [
                'json' => $payload,
            ]);

            $body = json_decode($response->getBody()->getContents(), true);

            if (!isset($body['choices'][0]['message']['content'])) {
                throw new \Exception('Respuesta de Groq no válida: ' . json_encode($body));
            }

            $generatedText = $body['choices'][0]['message']['content'];
            $usage = [
                'prompt_tokens'     => $body['usage']['prompt_tokens'] ?? 0,
                'completion_tokens' => $body['usage']['completion_tokens'] ?? 0,
                'total_tokens'      => $body['usage']['total_tokens'] ?? 0,
            ];

            if ($userId) {
                event(new VeraTokensConsumed(
                    userId: $userId,
                    actionType: $actionType,
                    tokenData: array_merge($usage, ['model' => $this->model]),
                    emailId: $emailId,
                    metadata: $metadata
                ));
            }

            Log::info('✅ Groq analyze completado', [
                'email_id' => $emailId,
                'tokens'   => $usage['total_tokens'],
            ]);

            return [
                'content'     => $generatedText,
                'tokens_used' => $usage['total_tokens'],
                'model'       => $this->model,
                'provider'    => 'groq',
            ];

        } catch (GuzzleException $e) {
            Log::error('❌ Error en Groq analyze (Guzzle)', ['error' => $e->getMessage()]);
            throw new \Exception('Error conectando con Groq: ' . $e->getMessage());
        } catch (\Exception $e) {
            Log::error('❌ Error en Groq analyze', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Clasificación rápida con modelo ligero (llama-3.1-8b-instant)
     */
    public function quickClassify(
        string $subject,
        string $bodyPreview,
        string $fromEmail,
        ?int $userId = null,
        ?int $emailId = null
    ): array {
        $this->enforceRateLimit();

        $prompt = <<<PROMPT
Clasifica este email. Responde SOLO con JSON, sin markdown.

De: {$fromEmail}
Asunto: {$subject}
Inicio: {$bodyPreview}

Reglas:
- ANALIZAR = email de persona real que puede necesitar respuesta o acción
- IGNORAR = newsletter, marketing, notificación automática, confirmación de pedido, auto-reply, OTP, factura automática

{"decision": "ANALIZAR|IGNORAR", "motivo": "una frase corta"}
PROMPT;

        try {
            $payload = [
                'model'       => $this->fastModel,
                'messages'    => [
                    ['role' => 'user', 'content' => $prompt],
                ],
                'temperature' => 0.1,
                'max_tokens'  => 80,
            ];

            $response = $this->client->post('chat/completions', [
                'json' => $payload,
            ]);

            $body = json_decode($response->getBody()->getContents(), true);

            $text       = $body['choices'][0]['message']['content'] ?? '{}';
            $tokensUsed = $body['usage']['total_tokens'] ?? 0;

            if ($userId) {
                event(new VeraTokensConsumed(
                    userId: $userId,
                    actionType: 'quick_classify',
                    tokenData: [
                        'prompt_tokens'     => $body['usage']['prompt_tokens'] ?? 0,
                        'completion_tokens' => $body['usage']['completion_tokens'] ?? 0,
                        'total_tokens'      => $tokensUsed,
                        'model'             => $this->fastModel,
                    ],
                    emailId: $emailId,
                    metadata: ['subject' => $subject]
                ));
            }

            $text = preg_replace('/```json\s*|\s*```/', '', $text);
            $data = json_decode(trim($text), true);

            $needsFullAnalysis = isset($data['decision']) && $data['decision'] === 'ANALIZAR';

            Log::info('⚡ Groq quickClassify completado', [
                'email_id' => $emailId,
                'decision' => $data['decision'] ?? 'unknown',
                'motivo'   => $data['motivo'] ?? '',
                'tokens'   => $tokensUsed,
                'model'    => $this->fastModel,
            ]);

            return [
                'needs_full_analysis' => $needsFullAnalysis,
                'reason'              => $data['motivo'] ?? '',
                'decision'            => $data['decision'] ?? 'IGNORAR',
                'tokens_used'         => $tokensUsed,
            ];

        } catch (\Exception $e) {
            Log::warning('⚠️ Groq quickClassify falló, asumiendo ANALIZAR (fail-safe)', [
                'email_id' => $emailId,
                'error'    => $e->getMessage(),
            ]);

            return [
                'needs_full_analysis' => true,
                'reason'              => 'classify_error_failsafe',
                'decision'            => 'ANALIZAR',
                'tokens_used'         => 0,
            ];
        }
    }

    public function isConfigured(): bool
    {
        return !empty($this->apiKey) && !empty($this->model);
    }

    public function testConnection(): array
    {
        try {
            $response = $this->generateContent('Responde solo "OK" si me entiendes.');

            return [
                'success'  => true,
                'message'  => 'Conexión exitosa con Groq',
                'model'    => $this->model,
                'response' => $response['text'],
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error conectando con Groq',
                'error'   => $e->getMessage(),
            ];
        }
    }

    public function getInfo(): array
    {
        return [
            'provider'           => 'groq',
            'model'              => $this->model,
            'fast_model'         => $this->fastModel,
            'api_key_configured' => !empty($this->apiKey),
            'api_key_preview'    => !empty($this->apiKey)
                ? substr($this->apiKey, 0, 10) . '...'
                : 'No configurada',
            'base_url'           => $this->baseUrl,
            'is_configured'      => $this->isConfigured(),
            'cost_per_1k_tokens' => 0.0,
            'free_tier'          => '14,400 requests/día',
        ];
    }
}
