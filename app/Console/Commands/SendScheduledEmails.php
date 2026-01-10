<?php

namespace App\Console\Commands;

use App\Services\ScheduledEmailService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SendScheduledEmails extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'emails:send-scheduled
                            {--dry-run : Ejecutar sin enviar realmente}
                            {--limit=50 : Límite de emails a procesar por ejecución}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Enviar emails programados cuya hora de envío ya ha llegado';

    protected $scheduledEmailService;

    /**
     * Create a new command instance.
     */
    public function __construct(ScheduledEmailService $scheduledEmailService)
    {
        parent::__construct();
        $this->scheduledEmailService = $scheduledEmailService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $isDryRun = $this->option('dry-run');
        $limit = (int) $this->option('limit');

        $this->info('📅 Buscando emails programados listos para enviar...');

        if ($isDryRun) {
            $this->warn('⚠️  Modo DRY-RUN activado - No se enviarán emails realmente');
        }

        try {
            // Obtener emails listos para enviar
            $scheduledEmails = $this->scheduledEmailService->getEmailsReadyToSend()
                ->take($limit);

            if ($scheduledEmails->isEmpty()) {
                $this->info('✅ No hay emails programados listos para enviar en este momento');
                return Command::SUCCESS;
            }

            $this->info("📧 Encontrados {$scheduledEmails->count()} emails para enviar");

            if ($isDryRun) {
                $this->table(
                    ['ID', 'Para', 'Asunto', 'Programado para'],
                    $scheduledEmails->map(fn($e) => [
                        $e->id,
                        \Illuminate\Support\Str::limit($e->to, 30),
                        \Illuminate\Support\Str::limit($e->subject, 40),
                        $e->scheduled_at->format('Y-m-d H:i')
                    ])
                );

                return Command::SUCCESS;
            }

            $sent = 0;
            $failed = 0;

            $bar = $this->output->createProgressBar($scheduledEmails->count());
            $bar->start();

            foreach ($scheduledEmails as $scheduledEmail) {
                $success = $this->scheduledEmailService->processScheduledEmail($scheduledEmail);

                if ($success) {
                    $sent++;
                } else {
                    $failed++;
                }

                $bar->advance();
            }

            $bar->finish();
            $this->newLine();

            $this->info("✅ {$sent} emails enviados exitosamente");

            if ($failed > 0) {
                $this->warn("⚠️  {$failed} emails fallaron (se reintentarán automáticamente)");
            }

            Log::info('Command SendScheduledEmails ejecutado', [
                'sent' => $sent,
                'failed' => $failed,
                'total' => $scheduledEmails->count(),
                'timestamp' => now(),
            ]);

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('❌ Error al enviar emails programados: ' . $e->getMessage());

            Log::error('Error en SendScheduledEmails command', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return Command::FAILURE;
        }
    }
}
