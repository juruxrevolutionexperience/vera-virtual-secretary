<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Sincronización incremental automática cada 2 minutos
        // Solo para cuentas que hayan completado la sincronización inicial
        $schedule->call(function () {
            $accounts = \App\Models\EmailAccount::where('sync_enabled', true)
                ->whereNotNull('initial_sync_completed_at')
                ->get();

            foreach ($accounts as $account) {
                \App\Jobs\IncrementalSyncEmailsJob::dispatch($account);
            }
        })->everyTwoMinutes()->name('incremental-sync-emails');

        // Refrescar tokens que están próximos a expirar (30 minutos antes)
        $schedule->call(function () {
            $accounts = \App\Models\EmailAccount::where('sync_enabled', true)
                ->whereNotNull('token_expires_at')
                ->where('token_expires_at', '<=', now()->addMinutes(30))
                ->get();

            foreach ($accounts as $account) {
                // Solo loggear por ahora, el refresh se hace automáticamente en GmailSyncService
                \Log::info('Token próximo a expirar', [
                    'account_id' => $account->id,
                    'expires_at' => $account->token_expires_at,
                ]);
            }
        })->hourly()->name('check-email-tokens');

        // Re-análisis semanal de Vera para que siga aprendiendo
        $schedule->command('vera:weekly-reanalysis')
            ->weekly()
            ->sundays()
            ->at('02:00')
            ->name('vera-weekly-reanalysis');

        // 🤖 NIVEL 2: Procesamiento automático de emails con Vera
        // Ejecutar cada 5 minutos para generar respuestas automáticas
        $schedule->command('vera:process-emails --recent=10 --limit=100')
            ->everyFiveMinutes()
            ->withoutOverlapping()
            ->name('vera-process-emails')
            ->onSuccess(function () {
                \Log::info('✅ Vera procesó emails exitosamente');
            })
            ->onFailure(function () {
                \Log::error('❌ Error en procesamiento de Vera');
            });
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
