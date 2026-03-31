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
        // ========================================
        // VERA — EMAIL PROCESSING
        // ========================================

        // Procesar emails entrantes de Gmail cada 5 minutos
        $schedule->command('vera:process-emails')
            ->everyFiveMinutes()
            ->name('vera-process-emails')
            ->withoutOverlapping()
            ->onFailure(function () {
                \Log::error('❌ Error procesando emails de Vera');
            });

        // ========================================
        // SEGURIDAD
        // ========================================

        // Limpiar sesiones expiradas diariamente a las 4 AM
        $schedule->command('sessions:clean-expired')
            ->daily()
            ->at('04:00')
            ->name('clean-expired-sessions')
            ->onSuccess(function () {
                \Log::info('✅ Sesiones expiradas limpiadas');
            })
            ->onFailure(function () {
                \Log::error('❌ Error limpiando sesiones expiradas');
            });

        // ========================================
        // FASE 1F: FIXES CRÍTICOS
        // ========================================

        // Resetear límites mensuales de Vera (día 1 de cada mes a la 1 AM)
        $schedule->command('vera:reset-monthly-limits --force')
            ->monthlyOn(1, '01:00')
            ->name('vera-reset-monthly-limits')
            ->onSuccess(function () {
                \Log::info('✅ Límites mensuales de Vera reseteados');
            })
            ->onFailure(function () {
                \Log::error('❌ Error reseteando límites mensuales de Vera');
            });

        // Procesar solicitudes RGPD pendientes diariamente a las 2 AM
        $schedule->command('security:process-gdpr')
            ->daily()
            ->at('02:00')
            ->name('process-gdpr-requests')
            ->onSuccess(function () {
                \Log::info('✅ Solicitudes RGPD procesadas');
            })
            ->onFailure(function () {
                \Log::error('❌ Error procesando solicitudes RGPD');
            });

        // Verificar contraseñas próximas a expirar diariamente a las 2:30 AM
        $schedule->command('app:check-password-expiry')
            ->daily()
            ->at('02:30')
            ->name('check-password-expiry')
            ->onSuccess(function () {
                \Log::info('✅ Contraseñas verificadas');
            })
            ->onFailure(function () {
                \Log::error('❌ Error verificando contraseñas');
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
