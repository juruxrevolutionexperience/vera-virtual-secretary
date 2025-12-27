<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // REGISTRA AQUÍ EL ALIAS
        $middleware->alias([
            'paid' => \App\Http\Middleware\EnsurePaid::class,
        ]);

        // Inertia middleware
        $middleware->web(append: [
            \App\Http\Middleware\HandleInertiaRequests::class,
        ]);

    })
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            // ... otros alias existentes
            'vera.configured' => \App\Http\Middleware\EnsureVeraConfigured::class,
            'vera.limit' => \App\Http\Middleware\VeraEmailLimitMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
