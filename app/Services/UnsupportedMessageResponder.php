<?php

namespace App\Services;

use App\Models\VeraConfig;

/**
 * Respuestas de Vera para mensajes que NO son de texto (PDF, audio/voz, imagen,
 * ubicación, vacío/otros). Evita que Vera ignore en silencio lo que manda el
 * cliente. Lógica compartida entre canales (WhatsApp, Telegram).
 */
class UnsupportedMessageResponder
{
    /**
     * Devuelve ['text' => string, 'notify_owner' => bool] para un tipo de mensaje.
     *
     * @param string $kind  document | audio | image | location | other
     */
    public static function build(VeraConfig $config, string $kind): array
    {
        $negocio = trim((string) $config->business_name) ?: 'el negocio';

        return match ($kind) {
            'document' => [
                'text'         => "Recibí tu documento, pero no puedo procesarlo automáticamente. Se lo paso a {$negocio} para que lo revisen.",
                'notify_owner' => true,
            ],
            'audio' => [
                'text'         => "Prefiero que me escribas para poder ayudarte mejor. ¿Qué necesitas?",
                'notify_owner' => false,
            ],
            'image' => [
                'text'         => "Recibí tu imagen. Si es una consulta, {$negocio} te contactará en breve.",
                'notify_owner' => true,
            ],
            'location' => [
                'text'         => self::locationText($config),
                'notify_owner' => false,
            ],
            default => [
                'text'         => "¡Hola! ¿En qué puedo ayudarte hoy?",
                'notify_owner' => false,
            ],
        };
    }

    /**
     * Si el cliente manda su ubicación, Vera responde con la del NEGOCIO + enlace
     * de Maps (el del dueño, o uno generado gratis desde la dirección).
     */
    private static function locationText(VeraConfig $config): string
    {
        $address = trim((string) $config->business_address);
        $maps = $config->maps_url
            ?: ($address !== '' ? 'https://www.google.com/maps/search/?api=1&query=' . urlencode($address) : null);

        if ($address === '' && !$maps) {
            return "¡Gracias! ¿En qué puedo ayudarte?";
        }

        $text = $address !== '' ? "Nosotros estamos en {$address}." : "Te paso nuestra ubicación.";
        if ($maps) {
            $text .= "\n\n" . $maps;
        }

        return $text;
    }
}
