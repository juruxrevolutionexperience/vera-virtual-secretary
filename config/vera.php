<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Vera AI Assistant Settings
    |--------------------------------------------------------------------------
    |
    | Configuración del asistente virtual Vera
    |
    */

    // Activar/desactivar Vera globalmente
    'enabled' => env('VERA_ENABLED', false),

    // Proveedor de IA
    'ai_provider' => env('VERA_AI_PROVIDER', 'openai'), // openai, anthropic, local

    // API Keys
    'openai_key' => env('OPENAI_API_KEY'),
    'anthropic_key' => env('ANTHROPIC_API_KEY'),

    // Modelo a usar
    'model' => env('VERA_AI_MODEL', 'gpt-4'),

    // Personalidad de Vera
    'personality' => [
        'name' => 'Vera',
        'role' => 'Asistente personal de email',
        'tone' => 'profesional pero amigable',
        'language' => 'es',
    ],

    // Límites de procesamiento
    'limits' => [
        'max_emails_per_batch' => 50,
        'max_tokens_per_request' => 2000,
        'retry_attempts' => 3,
    ],

    // Features activadas
    'features' => [
        'sentiment_analysis' => true,
        'topic_detection' => true,
        'priority_detection' => true,
        'auto_categorization' => true,
        'summary_generation' => true,
        'response_suggestions' => false, // Fase 4
        'auto_replies' => false,         // Fase 4
    ],

    // Thresholds de confianza
    'confidence' => [
        'min_for_auto_action' => 0.85,  // 85% para acción automática
        'min_for_suggestion' => 0.60,   // 60% para sugerencia
    ],

];
