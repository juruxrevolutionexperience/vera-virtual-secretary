<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\VeraConfig;
use App\Services\VeraIntelligentAnalyzer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class VeraWeeklyReanalysis extends Command
{
    protected $signature = 'vera:weekly-reanalysis
                            {--user-id= : ID específico de usuario para re-analizar}
                            {--force : Forzar re-análisis aunque no haya pasado el tiempo}';

    protected $description = 'Re-analiza los emails de usuarios para que Vera siga aprendiendo';

    public function handle(VeraIntelligentAnalyzer $analyzer)
    {
        $this->info('🔄 Iniciando re-análisis semanal de Vera...');
        $this->newLine();

        // Obtener usuarios a re-analizar
        $users = $this->getUsersToReanalyze();

        if ($users->isEmpty()) {
            $this->info('✅ No hay usuarios que requieran re-análisis en este momento');
            return Command::SUCCESS;
        }

        $this->info("👥 Usuarios a re-analizar: {$users->count()}");
        $this->newLine();

        $successCount = 0;
        $errorCount = 0;

        foreach ($users as $user) {
            try {
                $this->line("📊 Analizando usuario: {$user->name} (ID: {$user->id})");

                // Ejecutar análisis
                $analysis = $analyzer->analyzeUser($user);

                // Actualizar VeraConfig con los nuevos aprendizajes
                $config = VeraConfig::where('user_id', $user->id)->first();

                if ($config) {
                    $config->update([
                        'writing_style' => $analysis['writing_style'],
                        'last_re_analysis_at' => now(),
                        'analysis_summary' => [
                            'email_statistics' => $analysis['email_statistics'],
                            'communication_patterns' => $analysis['communication_patterns'],
                            'response_behavior' => $analysis['response_behavior'],
                            'conclusions' => $analysis['conclusions'],
                            'analyzed_at' => now()->toDateTimeString(),
                        ],
                    ]);

                    $this->info("   ✅ Configuración actualizada");
                    $this->line("   📝 Emails analizados: {$analysis['email_statistics']['total_emails']}");

                    if ($analysis['writing_style']['has_sent_emails']) {
                        $this->line("   ✍️  Tono detectado: {$analysis['writing_style']['tone']}");
                        $this->line("   📏 Longitud promedio: {$analysis['writing_style']['avg_email_length']} caracteres");
                    }

                    $successCount++;
                } else {
                    $this->warn("   ⚠️  No tiene VeraConfig, saltando...");
                }

                $this->newLine();

            } catch (\Exception $e) {
                $this->error("   ❌ Error: {$e->getMessage()}");
                $errorCount++;

                Log::error('Error en re-análisis semanal de Vera', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->newLine();
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->info('📊 RESUMEN:');
        $this->line("   ✅ Exitosos: {$successCount}");
        $this->line("   ❌ Errores: {$errorCount}");
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');

        return Command::SUCCESS;
    }

    /**
     * Obtener usuarios que necesitan re-análisis
     */
    protected function getUsersToReanalyze()
    {
        $userId = $this->option('user-id');
        $force = $this->option('force');

        // Si se especifica un usuario, solo ese
        if ($userId) {
            return User::where('id', $userId)->get();
        }

        // Si es forzado, todos los usuarios con VeraConfig
        if ($force) {
            return User::whereHas('veraConfig')->get();
        }

        // Usuarios que necesitan re-análisis según su frecuencia configurada
        return User::whereHas('veraConfig', function($query) {
            $query->where(function($q) {
                // Nunca ha sido re-analizado
                $q->whereNull('last_re_analysis_at')
                  // O ha pasado el tiempo configurado
                  ->orWhereRaw('DATEDIFF(NOW(), last_re_analysis_at) >= re_analysis_frequency_days');
            });
        })->get();
    }
}
