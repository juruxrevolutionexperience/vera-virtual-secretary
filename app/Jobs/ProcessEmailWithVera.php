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

    /**
     * Número de intentos antes de fallar
     */
    public $tries = 3;

    /**
     * Tiempo máximo de ejecución (2 minutos)
     */
    public $timeout = 120;

    /**
     * El email a procesar
     */
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
    public function handle(
        VeraService $veraService,
        \App\Services\VeraRulesEngine $rulesEngine,
        \App\Services\VeraResponseGenerator $responseGenerator,
        \App\Services\VeraActivityService $activityService
    ): void
    {
        // Verificar que Vera está habilitada
        if (!$veraService->isEnabled()) {
            Log::info('⏸️  Vera deshabilitada, saltando procesamiento', [
                'email_id' => $this->email->id,
            ]);
            return;
        }

        // Verificar que el email no haya sido procesado ya
        if ($this->email->vera_processed) {
            Log::info('⏭️  Email ya procesado por Vera, saltando', [
                'email_id' => $this->email->id,
            ]);
            return;
        }

        try {
            Log::info('🤖 Procesando email con Vera', [
                'email_id' => $this->email->id,
                'subject' => $this->email->subject,
                'from' => $this->email->from_email,
            ]);

            // Analizar email con Vera
            $analysis = $veraService->analyzeEmail($this->email);

            // Guardar análisis en BD
            $veraService->saveAnalysisToEmail($this->email, $analysis);

            // Registrar actividad de procesamiento
            $tokenUsage = \App\Models\VeraTokenUsage::where('email_id', $this->email->id)
                ->where('action_type', 'email_analysis')
                ->latest()
                ->first();

            $activityService->logEmailProcessed($this->email, $tokenUsage, [
                'method' => $analysis['method'] ?? 'unknown',
                'summary' => $analysis['summary'] ?? '',
                'priority' => $analysis['priority'] ?? 'normal',
                'sentiment' => $analysis['sentiment_label'] ?? 'neutral',
            ]);

            // Aplicar reglas automáticas
            $appliedRules = $rulesEngine->applyRules($this->email->fresh());

            // Generar respuesta automática si es necesario
            $responseGenerated = false;
            try {
                $response = $responseGenerator->generateResponse($this->email->fresh());
                if ($response) {
                    $responseGenerated = true;
                    Log::info('✉️  Respuesta automática generada', [
                        'email_id' => $this->email->id,
                        'action_id' => $response->id,
                        'confidence' => $response->confidence_score,
                    ]);
                }
            } catch (\Exception $e) {
                // No fallar todo el proceso si la generación de respuesta falla
                Log::warning('⚠️  Error generando respuesta automática (continuando procesamiento)', [
                    'email_id' => $this->email->id,
                    'error' => $e->getMessage(),
                ]);
            }

            Log::info('✅ Email procesado con Vera exitosamente', [
                'email_id' => $this->email->id,
                'method' => $analysis['method'] ?? 'unknown',
                'summary' => $analysis['summary'],
                'priority' => $analysis['priority'] ?? 'normal',
                'rules_applied' => count($appliedRules),
                'response_generated' => $responseGenerated,
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Error procesando email con Vera', [
                'email_id' => $this->email->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Marcar el email como no procesado para que se intente de nuevo
            $this->email->update([
                'vera_processed' => false,
            ]);

            // Re-lanzar excepción para que Laravel maneje los retries
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('❌ Job ProcessEmailWithVera falló permanentemente', [
            'email_id' => $this->email->id,
            'error' => $exception->getMessage(),
        ]);

        // Marcar email como no procesado para que se pueda intentar manualmente después
        $this->email->update([
            'vera_processed' => false,
        ]);
    }
}
