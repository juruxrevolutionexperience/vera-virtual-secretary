<?php

namespace App\Console\Commands;

use App\Services\AutoResponderService;
use App\Models\Email;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ProcessAutoResponders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'emails:process-auto-responders
                            {--dry-run : Ejecutar sin enviar realmente}
                            {--minutes=5 : Procesar emails de los últimos X minutos}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Procesar auto-responders para emails recientes no leídos';

    protected $autoResponderService;

    /**
     * Create a new command instance.
     */
    public function __construct(AutoResponderService $autoResponderService)
    {
        parent::__construct();
        $this->autoResponderService = $autoResponderService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $isDryRun = $this->option('dry-run');
        $minutes = (int) $this->option('minutes');

        $this->info("🤖 Procesando auto-responders para emails de los últimos {$minutes} minutos...");

        if ($isDryRun) {
            $this->warn('⚠️  Modo DRY-RUN activado - No se enviarán auto-respuestas realmente');
        }

        try {
            // Obtener emails recientes no procesados
            $recentEmails = Email::where('status', 'received')
                ->where('is_read', false)
                ->where('received_at', '>=', now()->subMinutes($minutes))
                ->whereDoesntHave('autoResponderHistory') // Emails que aún no han recibido auto-respuesta
                ->get();

            if ($recentEmails->isEmpty()) {
                $this->info('✅ No hay emails nuevos para procesar');
                return Command::SUCCESS;
            }

            $this->info("📧 Encontrados {$recentEmails->count()} emails para procesar");

            if ($isDryRun) {
                $this->table(
                    ['ID', 'De', 'Asunto', 'Recibido'],
                    $recentEmails->take(10)->map(fn($e) => [
                        $e->id,
                        \Illuminate\Support\Str::limit($e->from_email, 30),
                        \Illuminate\Support\Str::limit($e->subject, 40),
                        $e->received_at->format('Y-m-d H:i')
                    ])
                );

                return Command::SUCCESS;
            }

            $processed = 0;
            $responded = 0;

            $bar = $this->output->createProgressBar($recentEmails->count());
            $bar->start();

            foreach ($recentEmails as $email) {
                // Procesar email con auto-responders
                $this->autoResponderService->processIncomingEmail($email);

                $processed++;
                $bar->advance();
            }

            $bar->finish();
            $this->newLine();

            $this->info("✅ {$processed} emails procesados");
            $this->info("📤 Se verificaron todos los auto-responders activos");

            Log::info('Command ProcessAutoResponders ejecutado', [
                'processed' => $processed,
                'minutes_window' => $minutes,
                'timestamp' => now(),
            ]);

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('❌ Error al procesar auto-responders: ' . $e->getMessage());

            Log::error('Error en ProcessAutoResponders command', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return Command::FAILURE;
        }
    }
}
