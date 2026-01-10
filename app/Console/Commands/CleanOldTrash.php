<?php

namespace App\Console\Commands;

use App\Services\EmailWorkflowService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CleanOldTrash extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'emails:clean-old-trash
                            {--days=30 : Número de días para considerar emails antiguos}
                            {--dry-run : Ejecutar sin eliminar realmente}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Eliminar permanentemente emails de la papelera más antiguos que X días';

    protected $workflowService;

    /**
     * Create a new command instance.
     */
    public function __construct(EmailWorkflowService $workflowService)
    {
        parent::__construct();
        $this->workflowService = $workflowService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $days = (int) $this->option('days');
        $isDryRun = $this->option('dry-run');

        $this->info("🔍 Buscando emails en papelera más antiguos que {$days} días...");

        if ($isDryRun) {
            $this->warn('⚠️  Modo DRY-RUN activado - No se eliminarán emails realmente');
        }

        try {
            $cutoffDate = now()->subDays($days);

            // Contar emails que serían eliminados
            $count = \App\Models\Email::deleted()
                ->where('deleted_at_custom', '<', $cutoffDate)
                ->count();

            if ($count === 0) {
                $this->info('✅ No hay emails antiguos para eliminar');
                return Command::SUCCESS;
            }

            if ($isDryRun) {
                $this->info("✅ Se encontraron {$count} emails que serían eliminados");

                // Mostrar muestra
                $emails = \App\Models\Email::deleted()
                    ->where('deleted_at_custom', '<', $cutoffDate)
                    ->limit(10)
                    ->get(['id', 'subject', 'deleted_at_custom', 'from_email']);

                $this->table(
                    ['ID', 'Asunto', 'Eliminado el', 'De'],
                    $emails->map(fn($e) => [
                        $e->id,
                        \Illuminate\Support\Str::limit($e->subject, 30),
                        $e->deleted_at_custom->format('Y-m-d'),
                        \Illuminate\Support\Str::limit($e->from_email, 25)
                    ])
                );

                if ($count > 10) {
                    $this->info("... y " . ($count - 10) . " más");
                }

                return Command::SUCCESS;
            }

            // Confirmar acción
            if ($this->option('no-interaction')) {
                $confirm = true;
            } else {
                $confirm = $this->confirm(
                    "⚠️  ¿Estás seguro de eliminar PERMANENTEMENTE {$count} emails? Esta acción NO se puede deshacer.",
                    false
                );
            }

            if (!$confirm) {
                $this->warn('❌ Operación cancelada');
                return Command::SUCCESS;
            }

            // Ejecutar limpieza
            $deletedCount = $this->workflowService->cleanOldTrash($days);

            $this->info("✅ {$deletedCount} emails eliminados permanentemente de la papelera");

            Log::info('Command CleanOldTrash ejecutado', [
                'days' => $days,
                'emails_deleted' => $deletedCount,
                'cutoff_date' => $cutoffDate,
                'timestamp' => now(),
            ]);

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('❌ Error al limpiar papelera: ' . $e->getMessage());

            Log::error('Error en CleanOldTrash command', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return Command::FAILURE;
        }
    }
}
