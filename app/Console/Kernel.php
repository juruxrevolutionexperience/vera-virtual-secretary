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

        // Auditoría de seguridad semanal — cada lunes a las 9 AM
        $schedule->command('security:audit-check --notify=' . config('mail.from.address'))
            ->weekly()
            ->mondays()
            ->at('09:00')
            ->name('security-audit-check')
            ->onSuccess(function () {
                \Log::info('✅ Auditoría de seguridad completada — sin CVEs HIGH');
            })
            ->onFailure(function () {
                \Log::critical('🚨 Auditoría de seguridad — CVEs HIGH detectados, revisar inmediatamente');
            });

        // Purgar vera_activity_log con más de 90 días cada domingo a las 2 AM
        $schedule->command('vera:purge-activity-log --days=90')
            ->weekly()
            ->sundays()
            ->at('02:00')
            ->name('vera-purge-activity-log')
            ->onSuccess(function () {
                \Log::info('✅ Purga de vera_activity_log completada');
            })
            ->onFailure(function () {
                \Log::error('❌ Error en purga de vera_activity_log');
            });

        // Limpiar chunks inactivos de ChromaDB cada domingo a las 3 AM
        $schedule->command('vera:cleanup-chroma')
            ->weekly()
            ->sundays()
            ->at('03:00')
            ->name('vera-cleanup-chroma')
            ->onSuccess(function () {
                \Log::info('✅ Limpieza de chunks inactivos en ChromaDB completada');
            })
            ->onFailure(function () {
                \Log::error('❌ Error en limpieza de chunks inactivos en ChromaDB');
            });

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
