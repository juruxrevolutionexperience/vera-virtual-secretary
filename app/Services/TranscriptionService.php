<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Transcribe notas de voz a texto con Groq Whisper (endpoint OpenAI-compatible).
 * Tier gratuito de Groq; coste ínfimo. Devuelve null si falla → el caller cae a la
 * respuesta "prefiero que me escribas".
 */
class TranscriptionService
{
    public function transcribe(string $audioBytes, string $filename = 'audio.ogg'): ?string
    {
        $key = config('services.groq.key');
        if (!$key || $audioBytes === '') {
            return null;
        }

        try {
            $res = Http::withToken($key)
                ->timeout(30)
                ->attach('file', $audioBytes, $filename)
                ->post('https://api.groq.com/openai/v1/audio/transcriptions', [
                    'model'       => config('services.groq.whisper_model', 'whisper-large-v3'),
                    // Idioma fijo + contexto del dominio: en audios cortos (1-3s) la
                    // autodetección falla y Whisper confunde palabras clave ("reservar"
                    // transcrito como "rechazar/cancelar" en pruebas reales 2026-07-06).
                    'language'    => config('services.groq.whisper_language', 'es'),
                    'prompt'      => 'Cliente hablando con la recepcionista de un negocio: reservar cita, cancelar cita, horarios, precios, ubicación, cómo llegar.',
                    'temperature' => 0,
                ]);
        } catch (\Throwable $e) {
            Log::warning('Whisper: excepción al transcribir', ['error' => $e->getMessage()]);
            return null;
        }

        if (!$res->successful()) {
            Log::warning('Whisper: Groq rechazó la transcripción', ['status' => $res->status(), 'error' => $res->json('error.message') ?? $res->body()]);
            return null;
        }

        $text = trim((string) $res->json('text'));

        return $text !== '' ? $text : null;
    }
}
