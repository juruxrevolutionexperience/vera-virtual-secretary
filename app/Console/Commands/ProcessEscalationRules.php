<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\EscalationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ProcessEscalationRules extends Command
{
    protected $signature = 'emails:process-escalations
                           {--user= : ID del usuario específico (opcional)}
                           {--limit=100 : Límite de emails a procesar por usuario}
                           {--dry-run : Modo simulación sin ejecutar acciones}';

    protected $description = 'Procesar reglas de escalado automático para emails';

    protected EscalationService $escalationService;

    public function __construct(EscalationService $escalationService)
    {
        parent::__construct();
        $this->escalationService = $escalationService;
    }

    public function handle(): int
    {
        $this->info('⚡ Procesando reglas de escalado...');

        if ($this->option('dry-run')) {
            $this->warn('⚠️  Modo DRY-RUN activado - No se ejecutarán acciones realmente');
        }

        $totalStats = [
            'users_processed' => 0,
            'emails_processed' => 0,
            'rules_evaluated' => 0,
            'rules_matched' => 0,
            'actions_executed' => 0,
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

            foreach ($users as $user) {
                try {
                    $this->info("Usuario: {$user->name} (ID: {$user->id})");

                    if (!$this->option('dry-run')) {
                        $stats = $this->escalationService->processUserEscalations(
                            $user,
                            (int) $this->option('limit')
                        );

                        $totalStats['users_processed']++;
                        $totalStats['emails_processed'] += $stats['emails_processed'];
                        $totalStats['rules_evaluated'] += $stats['rules_evaluated'];
                        $totalStats['rules_matched'] += $stats['rules_matched'];
                        $totalStats['actions_executed'] += $stats['actions_executed'];
                        $totalStats['errors'] += $stats['errors'];

                        $this->line("  ✓ Emails procesados: {$stats['emails_processed']}");
                        $this->line("  ✓ Reglas evaluadas: {$stats['rules_evaluated']}");
                        $this->line("  ✓ Coincidencias: {$stats['rules_matched']}");
                        $this->line("  ✓ Acciones ejecutadas: {$stats['actions_executed']}");
                    } else {
                        $totalStats['users_processed']++;
                        $this->line("  (Modo dry-run)");
                    }

                    $this->newLine();
                } catch (\Exception $e) {
                    $totalStats['errors']++;
                    $this->error("  ❌ Error: {$e->getMessage()}");
                    Log::error('Error processing escalations for user', [
                        'user_id' => $user->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            // Mostrar resumen
            $this->info('✅ Procesamiento completado');
            $this->newLine();
            $this->table(
                ['Métrica', 'Cantidad'],
                [
                    ['Usuarios procesados', $totalStats['users_processed']],
                    ['Emails procesados', $totalStats['emails_processed']],
                    ['Reglas evaluadas', $totalStats['rules_evaluated']],
                    ['Reglas coincidentes', $totalStats['rules_matched']],
                    ['Acciones ejecutadas', $totalStats['actions_executed']],
                    ['Errores', $totalStats['errors']],
                ]
            );

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('❌ Error: ' . $e->getMessage());
            Log::error('Error in ProcessEscalationRules command', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return Command::FAILURE;
        }
    }
}
