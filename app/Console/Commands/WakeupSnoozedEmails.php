<?php

namespace App\Console\Commands;

use App\Services\EmailWorkflowService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class WakeupSnoozedEmails extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'emails:wakeup-snoozed
                            {--dry-run : Ejecutar sin realizar cambios}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Despertar emails que están en snooze y su tiempo ha expirado';

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
        $this->info('🔍 Buscando emails listos para despertar del snooze...');

        $isDryRun = $this->option('dry-run');

        if ($isDryRun) {
            $this->warn('⚠️  Modo DRY-RUN activado - No se realizarán cambios');
        }

        try {
            // Procesar wakeups
            if ($isDryRun) {
                // Solo contar en modo dry-run
                $count = \App\Models\Email::readyToWakeup()->count();
                $this->info("✅ Se encontraron {$count} emails listos para despertar");

                if ($count > 0) {
                    $emails = \App\Models\Email::readyToWakeup()
                        ->limit(10)
                        ->get(['id', 'subject', 'snoozed_until']);

                    $this->table(
                        ['ID', 'Asunto', 'Snoozed Until'],
                        $emails->map(fn($e) => [
                            $e->id,
                            \Illuminate\Support\Str::limit($e->subject, 40),
                            $e->snoozed_until->format('Y-m-d H:i:s')
                        ])
                    );

                    if ($count > 10) {
                        $this->info("... y " . ($count - 10) . " más");
                    }
                }
            } else {
                // Ejecutar realmente
                $count = $this->workflowService->processWakeups();

                $this->info("✅ {$count} emails despertados del snooze exitosamente");

                Log::info('Command WakeupSnoozedEmails ejecutado', [
                    'emails_woken' => $count,
                    'timestamp' => now(),
                ]);
            }

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('❌ Error al procesar wakeups: ' . $e->getMessage());

            Log::error('Error en WakeupSnoozedEmails command', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return Command::FAILURE;
        }
    }
}
