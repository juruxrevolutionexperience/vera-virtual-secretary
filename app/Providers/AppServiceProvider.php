<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Event;
use Stripe\Stripe;
use Stripe\ApiRequestor;

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

        // Registrar listener de sesiones de usuario
        Event::listen(
            \Illuminate\Auth\Events\Login::class,
            \App\Listeners\RecordUserSession::class,
        );
    }
}
