<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * AIGatewayService
 *
 * Proxy abstracto sobre los providers de IA con Circuit Breaker integrado.
 *
 * Circuit Breaker — 3 estados:
 *   CLOSED    → funcionamiento normal
 *   OPEN      → proveedor caído, devuelve fallback inmediato (ahorra llamadas)
 *   HALF-OPEN → después de 5 min, prueba una sola llamada para ver si volvió
 */
class AIGatewayService
{
    protected GeminiProvider|GroqProvider $provider;
    protected string $providerName;

    // ── Circuit Breaker config ──────────────────────────────────────────────
    protected const FAILURE_THRESHOLD  = 3;    // Fallos consecutivos para abrir
    protected const RECOVERY_TIMEOUT   = 300;  // Segundos hasta probar de nuevo (5 min)
    protected const CACHE_TTL          = 600;  // TTL de las claves en caché (10 min)

    public function __construct()
    {
        $this->providerName = config('vera.ai_provider', 'gemini');

        $this->provider = match ($this->providerName) {
            'groq'   => new GroqProvider(),
            'gemini' => new GeminiProvider(),
            default  => new GeminiProvider(),
        };

        Log::debug('🤖 AIGateway usando provider: ' . $this->providerName);
    }

    // =========================================================
    // MÉTODOS PÚBLICOS (con Circuit Breaker)
    // =========================================================

    /**
     * Analizar con IA. Si el circuito está ABIERTO lanza excepción
     * para que el caller use el fallback de reglas.
     */
    public function analyze(
        string $systemPrompt,
        string $userPrompt,
        ?int $userId = null,
        ?int $emailId = null,
        string $actionType = 'email_analysis',
        array $metadata = []
    ): array {
        $this->checkCircuit();

        try {
            $result = $this->provider->analyze(
                systemPrompt: $systemPrompt,
                userPrompt: $userPrompt,
                userId: $userId,
                emailId: $emailId,
                actionType: $actionType,
                metadata: $metadata
            );

            $this->recordSuccess();
            return $result;

        } catch (\Exception $e) {
            $this->recordFailure($e);
            throw $e;
        }
    }

    /**
     * Generar contenido libre con IA.
     */
    public function generateContent(string $prompt, array $options = []): array
    {
        $this->checkCircuit();

        try {
            $result = $this->provider->generateContent($prompt, $options);
            $this->recordSuccess();
            return $result;

        } catch (\Exception $e) {
            $this->recordFailure($e);
            throw $e;
        }
    }

    /**
     * Clasificación rápida (modelo dual barato → caro).
     */
    public function quickClassify(
        string $subject,
        string $bodyPreview,
        string $fromEmail,
        ?int $userId = null,
        ?int $emailId = null
    ): array {
        $this->checkCircuit();

        try {
            $result = $this->provider->quickClassify(
                subject: $subject,
                bodyPreview: $bodyPreview,
                fromEmail: $fromEmail,
                userId: $userId,
                emailId: $emailId
            );

            $this->recordSuccess();
            return $result;

        } catch (\Exception $e) {
            $this->recordFailure($e);
            throw $e;
        }
    }

    /**
     * Test de conexión (sin pasar por el circuit breaker — es un diagnóstico).
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
        return $this->providerName;
    }

    /**
     * Estado actual del Circuit Breaker (para dashboards/monitorización).
     */
    public function getCircuitStatus(): array
    {
        $state    = $this->getState();
        $failures = Cache::get($this->cacheKey('failures'), 0);
        $openedAt = Cache::get($this->cacheKey('opened_at'));

        $secondsUntilRetry = null;
        if ($state === 'open' && $openedAt) {
            $remaining = self::RECOVERY_TIMEOUT - (time() - $openedAt);
            $secondsUntilRetry = max(0, $remaining);
        }

        return [
            'provider'           => $this->providerName,
            'state'              => $state,
            'failures'           => $failures,
            'threshold'          => self::FAILURE_THRESHOLD,
            'seconds_until_retry'=> $secondsUntilRetry,
            'healthy'            => $state === 'closed',
        ];
    }

    /**
     * Resetear el Circuit Breaker manualmente (para admin/debug).
     */
    public function resetCircuit(): void
    {
        Cache::forget($this->cacheKey('state'));
        Cache::forget($this->cacheKey('failures'));
        Cache::forget($this->cacheKey('opened_at'));

        Log::info('🔄 Circuit Breaker reseteado manualmente', [
            'provider' => $this->providerName,
        ]);
    }

    // =========================================================
    // CIRCUIT BREAKER — Lógica interna
    // =========================================================

    /**
     * Comprueba si el circuito permite hacer la llamada.
     * Lanza excepción si está ABIERTO (y no ha pasado el timeout).
     */
    protected function checkCircuit(): void
    {
        $state = $this->getState();

        if ($state === 'closed') {
            return; // Normal, dejar pasar
        }

        if ($state === 'open') {
            $openedAt = Cache::get($this->cacheKey('opened_at'), 0);
            $elapsed  = time() - $openedAt;

            if ($elapsed < self::RECOVERY_TIMEOUT) {
                // Aún en timeout — bloquear la llamada
                $remaining = self::RECOVERY_TIMEOUT - $elapsed;

                Log::warning('⚡ Circuit Breaker ABIERTO — llamada bloqueada', [
                    'provider'  => $this->providerName,
                    'retry_in'  => "{$remaining}s",
                ]);

                throw new \RuntimeException(
                    "El proveedor de IA ({$this->providerName}) está temporalmente no disponible. " .
                    "Vera opera en modo solo-reglas. Reintento en {$remaining} segundos."
                );
            }

            // Timeout superado → pasar a HALF-OPEN y probar
            $this->setState('half-open');

            Log::info('⚡ Circuit Breaker HALF-OPEN — probando si el provider volvió', [
                'provider' => $this->providerName,
            ]);
        }

        // Si es 'half-open': dejamos pasar la llamada (la prueba)
    }

    /**
     * Registra un éxito → cierra el circuito si estaba half-open.
     */
    protected function recordSuccess(): void
    {
        $state = $this->getState();

        if ($state !== 'closed') {
            Log::info('✅ Circuit Breaker CERRADO — provider recuperado', [
                'provider' => $this->providerName,
            ]);
        }

        // Resetear todo
        Cache::forget($this->cacheKey('failures'));
        Cache::forget($this->cacheKey('opened_at'));
        $this->setState('closed');
    }

    /**
     * Registra un fallo → incrementa contador y abre el circuito si supera el umbral.
     */
    protected function recordFailure(\Exception $e): void
    {
        $failures = Cache::increment($this->cacheKey('failures'));

        // Asegurar TTL en el contador
        Cache::put($this->cacheKey('failures'), $failures, self::CACHE_TTL);

        Log::warning('⚡ Circuit Breaker: fallo registrado', [
            'provider' => $this->providerName,
            'failures' => $failures,
            'threshold'=> self::FAILURE_THRESHOLD,
            'error'    => $e->getMessage(),
        ]);

        if ($failures >= self::FAILURE_THRESHOLD) {
            $this->setState('open');
            Cache::put($this->cacheKey('opened_at'), time(), self::CACHE_TTL);

            Log::error('🔴 Circuit Breaker ABIERTO — demasiados fallos consecutivos', [
                'provider'     => $this->providerName,
                'failures'     => $failures,
                'recovery_in'  => self::RECOVERY_TIMEOUT . 's',
            ]);
        }
    }

    protected function getState(): string
    {
        return Cache::get($this->cacheKey('state'), 'closed');
    }

    protected function setState(string $state): void
    {
        Cache::put($this->cacheKey('state'), $state, self::CACHE_TTL);
    }

    protected function cacheKey(string $suffix): string
    {
        return "circuit_breaker_{$this->providerName}_{$suffix}";
    }
}
