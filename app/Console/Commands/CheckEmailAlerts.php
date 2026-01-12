<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\EmailAlertService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CheckEmailAlerts extends Command
{
    protected $signature = 'emails:check-alerts
                           {--user= : ID del usuario específico (opcional)}
                           {--dry-run : Modo simulación sin crear alertas}';

    protected $description = 'Verificar y crear alertas automáticas de emails (deadlines, sin respuesta, vencidos, etc)';

    protected EmailAlertService $alertService;

    public function __construct(EmailAlertService $alertService)
    {
        parent::__construct();
        $this->alertService = $alertService;
    }

    public function handle(): int
    {
        $this->info('🔔 Verificando alertas de emails...');

        if ($this->option('dry-run')) {
            $this->warn('⚠️  Modo DRY-RUN activado - No se crearán alertas realmente');
        }

        $totalStats = [
            'users_processed' => 0,
            'deadline_alerts' => 0,
            'no_response_alerts' => 0,
            'overdue_alerts' => 0,
            'high_priority_alerts' => 0,
            'expired_cleaned' => 0,
            'errors' => 0,
        ];

        try {
            // Obtener usuarios
            $users = $this->option('user')
                ? User::where('id', $this->option('user'))->get()
                : User::all();

            if ($users->isEmpty()) {
                $this->error('❌ No se encontraron usuarios');
                return Command::FAILURE;
            }

            $this->info("📊 Procesando {$users->count()} usuario(s)...\n");

            $progressBar = $this->output->createProgressBar($users->count());
            $progressBar->start();

            foreach ($users as $user) {
                try {
                    if (!$this->option('dry-run')) {
                        $stats = $this->alertService->runAllChecks($user);

                        $totalStats['users_processed']++;
                        $totalStats['deadline_alerts'] += $stats['deadline_alerts'];
                        $totalStats['no_response_alerts'] += $stats['no_response_alerts'];
                        $totalStats['overdue_alerts'] += $stats['overdue_alerts'];
                        $totalStats['high_priority_alerts'] += $stats['high_priority_alerts'];
                        $totalStats['expired_cleaned'] += $stats['expired_cleaned'];
                    } else {
                        $totalStats['users_processed']++;
                    }

                    $progressBar->advance();
                } catch (\Exception $e) {
                    $totalStats['errors']++;
                    Log::error('Error checking alerts for user', [
                        'user_id' => $user->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            $progressBar->finish();
            $this->newLine(2);

            // Mostrar resumen
            $this->info('✅ Verificación completada');
            $this->newLine();
            $this->table(
                ['Métrica', 'Cantidad'],
                [
                    ['Usuarios procesados', $totalStats['users_processed']],
                    ['Alertas de deadline', $totalStats['deadline_alerts']],
                    ['Alertas sin respuesta', $totalStats['no_response_alerts']],
                    ['Alertas vencidos', $totalStats['overdue_alerts']],
                    ['Alertas alta prioridad', $totalStats['high_priority_alerts']],
                    ['Alertas expiradas limpiadas', $totalStats['expired_cleaned']],
                    ['Errores', $totalStats['errors']],
                ]
            );

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('❌ Error: ' . $e->getMessage());
            Log::error('Error in CheckEmailAlerts command', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return Command::FAILURE;
        }
    }
}
