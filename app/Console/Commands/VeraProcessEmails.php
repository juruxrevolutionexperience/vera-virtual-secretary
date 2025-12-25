<?php

namespace App\Console\Commands;

use App\Models\Email;
use App\Jobs\ProcessEmailWithVera;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Comando para procesar emails recientes con Vera automáticamente
 *
 * Uso:
 * php artisan vera:process-emails
 * php artisan vera:process-emails --user=1
 * php artisan vera:process-emails --limit=50
 * php artisan vera:process-emails --recent=10  (últimos 10 minutos)
 */
class VeraProcessEmails extends Command
{
    protected $signature = 'vera:process-emails
                            {--user= : ID del usuario específico}
                            {--limit=50 : Máximo de emails a procesar}
                            {--recent=10 : Procesar emails de los últimos N minutos}
                            {--force : Reprocesar emails ya procesados}';

    protected $description = 'Procesar emails recientes con Vera (análisis + respuestas automáticas)';

    public function handle()
    {
        $this->info('🤖 Vera - Procesamiento automático de emails');
        $this->newLine();

        $userId = $this->option('user');
        $limit = (int) $this->option('limit');
        $recent = (int) $this->option('recent');
        $force = $this->option('force');

        // Construir query
        $query = Email::query()
            ->where('status', 'received')
            ->where('is_archived', false);

        // Filtrar por usuario
        if ($userId) {
            $query->where('user_id', $userId);
            $this->info("👤 Usuario: {$userId}");
        }

        // Emails recientes (últimos N minutos)
        if ($recent > 0) {
            $query->where('received_at', '>=', now()->subMinutes($recent));
            $this->info("⏰ Últimos {$recent} minutos");
        }

        // Solo no procesados (a menos que se fuerce)
        if (!$force) {
            $query->where(function($q) {
                $q->whereNull('vera_processed')
                  ->orWhere('vera_processed', false);
            });
        }

        // Aplicar límite
        $query->limit($limit);

        // Obtener emails
        $emails = $query->orderBy('received_at', 'desc')->get();

        if ($emails->isEmpty()) {
            $this->warn('⚠️  No hay emails para procesar');
            return Command::SUCCESS;
        }

        $this->info("📧 Procesando {$emails->count()} emails...");
        $this->newLine();

        $bar = $this->output->createProgressBar($emails->count());
        $bar->setFormat(' %current%/%max% [%bar%] %message%');
        $bar->start();

        $processed = 0;
        $skipped = 0;
        $errors = 0;

        foreach ($emails as $email) {
            try {
                $bar->setMessage("Email #{$email->id} de {$email->from_email}");

                // Verificar si ya tiene una respuesta generada
                $hasResponse = \App\Models\VeraEmailAction::where('email_id', $email->id)
                    ->where('action_type', 'replied')
                    ->exists();

                if ($hasResponse && !$force) {
                    $skipped++;
                    $bar->advance();
                    continue;
                }

                // Disparar Job de procesamiento
                ProcessEmailWithVera::dispatch($email);
                $processed++;

            } catch (\Exception $e) {
                $errors++;
                Log::error('Error procesando email', [
                    'email_id' => $email->id,
                    'error' => $e->getMessage(),
                ]);
            }

            $bar->advance();
        }

        $bar->setMessage('¡Completado!');
        $bar->finish();
        $this->newLine(2);

        // Resumen
        $this->info('╔══════════════════════════════════════╗');
        $this->info('║        RESUMEN DE PROCESAMIENTO      ║');
        $this->info('╚══════════════════════════════════════╝');
        $this->newLine();

        $this->table(
            ['Métrica', 'Cantidad'],
            [
                ['Total emails encontrados', $emails->count()],
                ['Jobs despachados', $processed],
                ['Ya procesados (saltados)', $skipped],
                ['Errores', $errors],
            ]
        );

        $this->newLine();

        if ($processed > 0) {
            $this->info('✅ Los emails están siendo procesados en segundo plano');
            $this->info('💡 Ver logs en: storage/logs/laravel.log');
        }

        if ($skipped > 0) {
            $this->comment("ℹ️  {$skipped} emails ya tenían respuestas generadas (usa --force para reprocesar)");
        }

        return Command::SUCCESS;
    }
}
