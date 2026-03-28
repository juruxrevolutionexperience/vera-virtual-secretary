<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Vera AI Assistant Settings
    |--------------------------------------------------------------------------
    |
    | Configuración del asistente virtual Vera — Secretaria de Agenda
    |
    */

    // Activar/desactivar Vera globalmente
    'enabled' => env('VERA_ENABLED', false),

    // Proveedor de IA principal
    'ai_provider' => env('VERA_AI_PROVIDER', 'groq'), // groq, gemini

    // API Keys
    'gemini_key' => env('GEMINI_API_KEY'),
    'groq_key'   => env('GROQ_API_KEY'),

    // Modelos
    'gemini_model'    => env('GEMINI_MODEL', 'gemini-2.0-flash'),
    'groq_model'      => env('GROQ_MODEL', 'llama-3.3-70b-versatile'),
    'groq_fast_model' => env('GROQ_FAST_MODEL', 'llama-3.1-8b-instant'),

    // Personalidad de Vera
    'personality' => [
        'name'     => 'Vera',
        'role'     => 'Secretaria de agenda y reuniones',
        'tone'     => 'profesional pero amigable',
        'language' => 'es',
    ],

    // Thresholds de confianza (Sprint 2: Cerebro de Vera)
    'confidence' => [
        'min_rag_score'     => 0.75,  // Score mínimo para usar chunk de RAG
        'min_for_auto_book' => 0.85,  // Score mínimo para confirmar reunión automáticamente
    ],

];
