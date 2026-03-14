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

        // 📬 Renovar Gmail watches antes de que expiren (duran 7 días máx)
        $schedule->command('gmail:renew-watches')
            ->dailyAt('06:00')
            ->withoutOverlapping()
            ->name('gmail-renew-watches');

        // ⏰ Enviar respuestas de Vera programadas con delay (cada minuto)
        $schedule->command('vera:send-scheduled')
            ->everyMinute()
            ->withoutOverlapping()
            ->name('vera-send-scheduled-responses');

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

        // ========================================
        // FASE 1A: WORKFLOW Y GESTIÓN
        // ========================================

        // Despertar emails del snooze cada 5 minutos
        $schedule->command('emails:wakeup-snoozed')
            ->everyFiveMinutes()
            ->name('wakeup-snoozed-emails')
            ->onSuccess(function () {
                \Log::info('✅ Emails despertados del snooze');
            });

        // Limpiar papelera antigua (emails > 30 días) diariamente a las 3 AM
        $schedule->command('emails:clean-old-trash --days=30')
            ->daily()
            ->at('03:00')
            ->name('clean-old-trash')
            ->onSuccess(function () {
                \Log::info('✅ Papelera limpiada exitosamente');
            })
            ->onFailure(function () {
                \Log::error('❌ Error al limpiar papelera');
            });

        // ========================================
        // FASE 1B: AUTOMATIZACIÓN Y TEMPLATES
        // ========================================

        // Enviar emails programados cada minuto
        $schedule->command('emails:send-scheduled --limit=50')
            ->everyMinute()
            ->withoutOverlapping()
            ->name('send-scheduled-emails')
            ->onSuccess(function () {
                \Log::info('✅ Emails programados enviados');
            })
            ->onFailure(function () {
                \Log::error('❌ Error enviando emails programados');
            });

        // Procesar auto-responders cada 5 minutos
        $schedule->command('emails:process-auto-responders --minutes=5')
            ->everyFiveMinutes()
            ->withoutOverlapping()
            ->name('process-auto-responders')
            ->onSuccess(function () {
                \Log::info('✅ Auto-responders procesados');
            })
            ->onFailure(function () {
                \Log::error('❌ Error procesando auto-responders');
            });

        // ========================================
        // FASE 1C: ALERTAS Y SEGUIMIENTO
        // ========================================

        // Verificar y crear alertas de emails cada hora
        $schedule->command('emails:check-alerts')
            ->hourly()
            ->withoutOverlapping()
            ->name('check-email-alerts')
            ->onSuccess(function () {
                \Log::info('✅ Alertas de emails verificadas');
            })
            ->onFailure(function () {
                \Log::error('❌ Error verificando alertas');
            });

        // Procesar reglas de escalado cada 30 minutos
        $schedule->command('emails:process-escalations --limit=100')
            ->everyThirtyMinutes()
            ->withoutOverlapping()
            ->name('process-escalations')
            ->onSuccess(function () {
                \Log::info('✅ Reglas de escalado procesadas');
            })
            ->onFailure(function () {
                \Log::error('❌ Error procesando escalados');
            });

        // Enviar recordatorios de seguimiento cada 15 minutos
        $schedule->command('emails:send-follow-up-reminders')
            ->everyFifteenMinutes()
            ->withoutOverlapping()
            ->name('send-follow-up-reminders')
            ->onSuccess(function () {
                \Log::info('✅ Recordatorios enviados');
            })
            ->onFailure(function () {
                \Log::error('❌ Error enviando recordatorios');
            });

        // Sincronizar contactos desde emails diariamente a las 3 AM
        $schedule->command('contacts:sync-from-emails')
            ->daily()
            ->at('03:00')
            ->withoutOverlapping()
            ->name('sync-contacts')
            ->onSuccess(function () {
                \Log::info('✅ Contactos sincronizados desde emails');
            })
            ->onFailure(function () {
                \Log::error('❌ Error sincronizando contactos');
            });

        // ========================================
        // FASE 1E: SEGURIDAD
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
