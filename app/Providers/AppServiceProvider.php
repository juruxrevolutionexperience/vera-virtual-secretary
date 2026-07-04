<?php

namespace App\Providers;

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\ServiceProvider;
use Stripe\ApiRequestor;
use Stripe\Stripe;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // TEMPORAL: Solo para desarrollo local
        // Configura Stripe para desactivar verificación SSL
        if (app()->environment('local')) {
            Stripe::setApiKey(config('cashier.secret'));

            // Desactivar verificación SSL solo en local
            ApiRequestor::setHttpClient(
                new \Stripe\HttpClient\CurlClient([
                    CURLOPT_SSL_VERIFYPEER => false,
                    CURLOPT_SSL_VERIFYHOST => false,
                ])
            );
        }

        // ── Scheduler de Vera ─────────────────────────────────────────────────

        // Procesar emails entrantes de Gmail — cada 5 min para no saturar Gmail
        // ni sobrecargar el server cuando haya muchos users
        Schedule::command('vera:process-emails')
            ->everyFiveMinutes()
            ->name('vera-process-emails')
            ->withoutOverlapping()
            ->onFailure(function () {
                \Log::error('❌ Error procesando emails de Vera');
            });

        // Recordatorios de cita (24h y 1h antes) — cada 15 min para acertar la franja
        Schedule::command('vera:send-reminders')
            ->everyFifteenMinutes()
            ->name('vera-send-reminders')
            ->withoutOverlapping();

        // Seguimiento a leads que recibieron el enlace de reserva y no agendaron en 24h
        Schedule::command('vera:send-followups')
            ->hourly()
            ->name('vera-send-followups')
            ->withoutOverlapping();

        // Limpiar sesiones expiradas — diariamente a las 4 AM
        Schedule::command('sessions:clean-expired')
            ->daily()
            ->at('04:00')
            ->name('clean-expired-sessions')
            ->onSuccess(function () { \Log::info('✅ Sesiones expiradas limpiadas'); })
            ->onFailure(function () { \Log::error('❌ Error limpiando sesiones expiradas'); });

        // Auditoría de seguridad semanal — cada lunes a las 9 AM
        Schedule::command('security:audit-check --notify=' . config('mail.from.address'))
            ->weekly()
            ->mondays()
            ->at('09:00')
            ->name('security-audit-check')
            ->onSuccess(function () { \Log::info('✅ Auditoría de seguridad completada — sin CVEs HIGH'); })
            ->onFailure(function () { \Log::critical('🚨 Auditoría de seguridad — CVEs HIGH detectados'); });

        // Purgar vera_activity_log con más de 90 días — cada domingo a las 2 AM
        Schedule::command('vera:purge-activity-log --days=90')
            ->weekly()
            ->sundays()
            ->at('02:00')
            ->name('vera-purge-activity-log')
            ->onSuccess(function () { \Log::info('✅ Purga de vera_activity_log completada'); })
            ->onFailure(function () { \Log::error('❌ Error en purga de vera_activity_log'); });

        // Limpiar chunks inactivos de ChromaDB — cada domingo a las 3 AM
        Schedule::command('vera:cleanup-chroma')
            ->weekly()
            ->sundays()
            ->at('03:00')
            ->name('vera-cleanup-chroma')
            ->onSuccess(function () { \Log::info('✅ Limpieza de chunks inactivos en ChromaDB completada'); })
            ->onFailure(function () { \Log::error('❌ Error en limpieza de chunks inactivos en ChromaDB'); });

        // Resetear límites mensuales — día 1 de cada mes a la 1 AM
        Schedule::command('vera:reset-monthly-limits --force')
            ->monthlyOn(1, '01:00')
            ->name('vera-reset-monthly-limits')
            ->onSuccess(function () { \Log::info('✅ Límites mensuales de Vera reseteados'); })
            ->onFailure(function () { \Log::error('❌ Error reseteando límites mensuales de Vera'); });

        // Retención RGPD: anonimizar conversaciones >24 meses — día 2 de cada mes a las 3:30 AM
        Schedule::command('vera:retention-cleanup --months=24')
            ->monthlyOn(2, '03:30')
            ->name('vera-retention-cleanup')
            ->onSuccess(function () { \Log::info('✅ Retención RGPD aplicada'); })
            ->onFailure(function () { \Log::error('❌ Error aplicando retención RGPD'); });

        // Procesar solicitudes RGPD pendientes — diariamente a las 2 AM
        Schedule::command('security:process-gdpr')
            ->daily()
            ->at('02:00')
            ->name('process-gdpr-requests')
            ->onSuccess(function () { \Log::info('✅ Solicitudes RGPD procesadas'); })
            ->onFailure(function () { \Log::error('❌ Error procesando solicitudes RGPD'); });

        // Verificar contraseñas próximas a expirar — diariamente a las 2:30 AM
        Schedule::command('app:check-password-expiry')
            ->daily()
            ->at('02:30')
            ->name('check-password-expiry')
            ->onSuccess(function () { \Log::info('✅ Contraseñas verificadas'); })
            ->onFailure(function () { \Log::error('❌ Error verificando contraseñas'); });

        // Registrar listener de sesiones de usuario
        Event::listen(
            \Illuminate\Auth\Events\Login::class,
            \App\Listeners\RecordUserSession::class,
        );

        // Email de verificación personalizado — branding Jurux Revolution en español
        VerifyEmail::toMailUsing(function ($notifiable, string $url) {
            return (new MailMessage)
                ->subject('Verifica tu dirección de correo')
                ->greeting('¡Hola!')
                ->line('Gracias por unirte a Vera. Pulsa el botón de abajo para verificar tu dirección de correo electrónico.')
                ->action('Verificar correo', $url)
                ->line('Si no creaste una cuenta en Vera, puedes ignorar este mensaje.')
                ->salutation("Un saludo,\nEl equipo de Jurux Revolution");
        });
    }
}
