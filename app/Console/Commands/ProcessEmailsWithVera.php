<?php

namespace App\Console\Commands;

use App\Jobs\ProcessEmailWithVera;
use App\Models\Email;
use App\Services\VeraService;
use Illuminate\Console\Command;

class ProcessEmailsWithVera extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'vera:process-emails
                            {--limit= : Número máximo de emails a procesar}
                            {--reprocess : Reprocesar emails ya analizados}
                            {--user= : Procesar solo emails de un usuario específico}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Procesar emails existentes con Vera (IA)';

    /**
     * Execute the console command.
     */
    public function handle(VeraService $veraService)
    {
        $this->info('🤖 Vera - Procesador de Emails');
        $this->newLine();

        // Verificar que Vera está habilitada
        if (!$veraService->isEnabled()) {
            $this->error('❌ Vera está deshabilitada en la configuración');
            $this->info('💡 Activa Vera en el archivo .env: VERA_ENABLED=true');
            return 1;
        }

        // Construir query
        $query = Email::query();

        // Filtrar por usuario si se especifica
        if ($userId = $this->option('user')) {
            $query->where('user_id', $userId);
        }

        // Filtrar emails no procesados (o todos si --reprocess)
        if (!$this->option('reprocess')) {
            $query->where('vera_processed', false);
        }

        // Aplicar límite si se especifica
        if ($limit = $this->option('limit')) {
            $query->limit((int) $limit);
        }

        // Obtener emails
        $emails = $query->orderBy('received_at', 'desc')->get();
        $total = $emails->count();

        if ($total === 0) {
            $this->info('✅ No hay emails para procesar');
            return 0;
        }

        $this->info("📊 Emails a procesar: {$total}");
        $this->newLine();

        // Confirmar si son muchos emails
        if ($total > 100 && !$this->option('limit')) {
            if (!$this->confirm("⚠️  Vas a procesar {$total} emails. ¿Continuar?", true)) {
                $this->info('❌ Operación cancelada');
                return 0;
            }
        }

        // Procesar emails con barra de progreso
        $bar = $this->output->createProgressBar($total);
        $bar->setFormat(' %current%/%max% [%bar%] %percent:3s%% - %message%');
        $bar->setMessage('Iniciando...');
        $bar->start();

        $processed = 0;
        $queued = 0;
        $skipped = 0;

        foreach ($emails as $email) {
            try {
                // Disparar Job (se procesa en background)
                ProcessEmailWithVera::dispatch($email);
                $queued++;

                $bar->setMessage("📧 {$email->subject}");
                $bar->advance();

            } catch (\Exception $e) {
                $skipped++;
                $bar->setMessage("⚠️  Error: {$email->subject}");
                $bar->advance();

                $this->newLine();
                $this->error("Error en email {$email->id}: {$e->getMessage()}");
            }

            $processed++;
        }

        $bar->setMessage('Completado');
        $bar->finish();
        $this->newLine(2);

        // Resumen
        $this->info('✅ Procesamiento iniciado');
        $this->table(
            ['Métrica', 'Valor'],
            [
                ['Emails procesados', $processed],
                ['Jobs en cola', $queued],
                ['Saltados', $skipped],
            ]
        );

        $this->newLine();
        $this->info('💡 Los emails se están procesando en background');
        $this->info('📊 Revisa los logs con: sail artisan tail');

        return 0;
    }
}
