<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'google_analytics' => [
        'id' => env('GOOGLE_ANALYTICS_ID'),
    ],

    // Google OAuth — Calendar
    'google' => [
        'client_id'     => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect'      => env('APP_URL') . '/google/calendar/callback',
    ],

    // WhatsApp Business API (Meta)
    'whatsapp' => [
        'verify_token' => env('WHATSAPP_VERIFY_TOKEN'),
        'app_secret'   => env('WHATSAPP_APP_SECRET'),
        'api_version'  => env('WHATSAPP_API_VERSION', 'v25.0'),
    ],

    // Telegram Bot API
    'telegram' => [
        'bot_token'      => env('TELEGRAM_BOT_TOKEN'),
        'webhook_secret' => env('TELEGRAM_WEBHOOK_SECRET'),
    ],

    // ai-service Python FastAPI (Roque)
    'ai_service' => [
        'url'    => env('AI_SERVICE_URL'),
        'secret' => env('AI_SERVICE_SECRET'),
    ],

    // Groq — transcripción de notas de voz (Whisper). Tier gratis; coste ínfimo.
    'groq' => [
        'key'           => env('GROQ_API_KEY'),
        'whisper_model' => env('GROQ_WHISPER_MODEL', 'whisper-large-v3'),
    ],

    // Stripe — IDs de precio de cada plan. Se leen vía config() (no env() directo),
    // porque con la config cacheada en producción env() devuelve null.
    'stripe' => [
        'prices' => [
            'essentials'   => env('STRIPE_PRICE_ESSENTIALS'),
            'professional' => env('STRIPE_PRICE_PROFESSIONAL'),
            'business'     => env('STRIPE_PRICE_BUSINESS'),
        ],
    ],

];
