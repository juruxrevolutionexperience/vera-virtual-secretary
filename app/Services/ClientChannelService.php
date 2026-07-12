<?php

namespace App\Services;

use App\Models\Contact;
use App\Models\VeraConfig;
use Illuminate\Support\Facades\Log;

/**
 * Envío de mensajes al cliente por su canal de mensajería (telegram/whatsapp),
 * en frío (fuera del ciclo de un webhook). Lo usan las acciones de la Bandeja:
 * responder incidencias, responder consultas y el mensaje de cierre.
 */
class ClientChannelService
{
    /**
     * Devuelve true/false según el envío, o null si el contacto de Telegram aún
     * no tiene chat_id persistido (escribió antes de que se guardara).
     */
    public function send(?VeraConfig $config, string $channel, string $contactIdentifier, ?Contact $contact, string $text): ?bool
    {
        if ($channel === 'telegram' && $config?->telegram_bot_token) {
            // chat_id numérico persistido en el contacto ("@username" no vale para
            // chats privados). Fallback: telegram_id si es numérico (sin username).
            $contact ??= Contact::where('user_id', $config->user_id)
                ->where('telegram_id', $contactIdentifier)->first();
            $chatId = $contact?->metadata['telegram_chat_id']
                ?? (is_numeric($contact?->telegram_id) ? $contact->telegram_id : null);

            if (!$chatId) {
                Log::warning('bandeja: contacto telegram sin chat_id persistido', [
                    'contact' => mb_substr($contactIdentifier, 0, 4) . '…',
                ]);
                return null;
            }

            return (new TelegramService())->sendMessage($config->telegram_bot_token, $chatId, $text);
        }

        if ($channel === 'whatsapp' && $config?->hasWhatsAppConnected()) {
            return (new WhatsAppService())->sendText(
                $config->whatsapp_phone_number_id,
                $config->whatsapp_access_token,
                $contactIdentifier,
                $text
            );
        }

        return false;
    }
}
