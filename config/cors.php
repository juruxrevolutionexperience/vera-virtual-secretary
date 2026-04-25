<?php

return [

    /*
    |--------------------------------------------------------------------------
    | CORS — Solo se aplica a peticiones de API (XHR/fetch desde el navegador)
    | Los webhooks (WhatsApp, Telegram, Stripe) no usan CORS porque vienen
    | de servidores externos, no de navegadores.
    |--------------------------------------------------------------------------
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],

    'allowed_origins' => [
        'https://jurux.app',
        'https://app.jurux.app',
    ],

    'allowed_origins_patterns' => [
        '#^http://localhost(:\d+)?$#',  // desarrollo local en cualquier puerto
    ],

    'allowed_headers' => [
        'Content-Type',
        'Authorization',
        'X-Requested-With',
        'X-CSRF-TOKEN',
        'X-Inertia',
        'X-Inertia-Version',
    ],

    'exposed_headers' => [
        'X-Inertia',
    ],

    'max_age' => 86400, // cachear preflight 24h

    'supports_credentials' => true,

];
