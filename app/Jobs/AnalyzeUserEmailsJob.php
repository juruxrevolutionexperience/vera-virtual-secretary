<?php

namespace App\Jobs;

use App\Models\User;
use App\Models\Email;
use App\Models\VeraConfig;
use App\Services\VeraIntelligentAnalyzer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class AnalyzeUserEmailsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $userId;

    public function __construct($userId)
    {
        $this->userId = $userId;
    }

    public function handle(VeraIntelligentAnalyzer $analyzer)
    {
        try {
            $user = User::find($this->userId);
            
            if (!$user) {
                Log::warning("Usuario no encontrado para análisis: {$this->userId}");
                return;
            }

            // Verificar que tenga emails
            $emailsCount = Email::whereHas('emailAccount', function($query) use ($user) {
                $query->where('user_id', $user->id);
            })->count();

            if ($emailsCount === 0) {
                Log::info("No hay emails para analizar para usuario: {$this->userId}");
                return;
            }

            // Ejecutar análisis
            Log::info("Iniciando análisis de Vera para usuario: {$this->userId}");
            $analysis = $analyzer->analyzeUser($user);

            // Verificar si el usuario necesita onboarding
            $config = VeraConfig::where('user_id', $user->id)->first();

            if (!$config) {
                // Usuario no ha configurado Vera, marcar para onboarding
                Log::info("Usuario {$this->userId} necesita onboarding de Vera");

                // Guardar flag en sesión para mostrar modal
                cache()->put("vera_needs_onboarding_{$user->id}", true, now()->addDays(7));
            } else {
                // Actualizar fecha de último análisis
                $config->update([
                    'last_analysis_at' => now(),
                ]);
            }

            Log::info("Análisis de Vera completado para usuario: {$this->userId}", [
                'total_emails' => $analysis['email_statistics']['total_emails'] ?? 0,
                'needs_onboarding' => !$config,
            ]);

        } catch (\Exception $e) {
            Log::error("Error en análisis de Vera para usuario {$this->userId}: " . $e->getMessage());
            throw $e;
        }
    }
}
