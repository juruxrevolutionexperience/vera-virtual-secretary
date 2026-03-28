<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessIncomingMessage;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * TelegramWebhookController — Sprint 3
 *
 * Recibe updates del Telegram Bot API via webhook.
 *
 * Configura el webhook con:
 * curl "https://api.telegram.org/bot{TOKEN}/setWebhook?url=https://tu-dominio.com/webhooks/telegram/{SECRET}"
 *
 * El SECRET en la URL actúa como token de verificación.
 */
class TelegramWebhookController extends Controller
{
    /**
     * Recibe updates de Telegram (POST).
     * El {secret} en la URL verifica que la petición viene de Telegram.
     */
    public function handle(Request $request, string $secret)
    {
        // Verificar token secreto en la URL
        if (!$this->verifySecret($secret)) {
            Log::warning('❌ Telegram webhook secret inválido', ['ip' => $request->ip()]);
            return response('Unauthorized', 401);
        }

        $update = $request->json()->all();

        try {
            $this->dispatchUpdate($update);
        } catch (\Exception $e) {
            Log::error('❌ Error despachando update Telegram', ['error' => $e->getMessage()]);
        }

        // Telegram requiere responder 200 OK rápido
        return response('OK', 200);
    }

    /**
     * Extrae el mensaje del Update de Telegram y despacha un Job.
     */
    private function dispatchUpdate(array $update): void
    {
        // Solo procesar mensajes de texto por ahora
        $message = $update['message'] ?? $update['channel_post'] ?? null;

        if (!$message) {
            return; // Podría ser edited_message, callback_query, etc.
        }

        $text = $message['text'] ?? null;
        $chat = $message['chat'] ?? [];
        $from = $message['from'] ?? [];

        if (!$text) {
            return; // Mensaje sin texto (foto, sticker, etc.)
        }

        $chatId       = $chat['id'] ?? null;
        $contactName  = trim(($from['first_name'] ?? '') . ' ' . ($from['last_name'] ?? '')) ?: null;
        $username     = $from['username'] ?? null;
        $updateId     = $update['update_id'] ?? null;

        if (!$chatId) {
            return;
        }

        // Identificador del contacto: username si existe, si no chat_id
        $contactIdentifier = $username ? "@{$username}" : (string) $chatId;

        // Encontrar el usuario dueño de este bot de Telegram
        $user = $this->findUserByChatContext($chatId);

        if (!$user) {
            Log::warning('Telegram: no se encontró usuario para chat', [
                'chat_id' => $chatId,
            ]);
            return;
        }

        Log::info('✈️ Mensaje Telegram recibido', [
            'user_id'  => $user->id,
            'chat_id'  => $chatId,
            'length'   => strlen($text),
        ]);

        ProcessIncomingMessage::dispatch(
            userId:            $user->id,
            channel:           'telegram',
            message:           $text,
            contactIdentifier: $contactIdentifier,
            contactName:       $contactName,
            metadata:          ['chat_id' => $chatId, 'update_id' => $updateId, 'username' => $username]
        );
    }

    /**
     * Encuentra al usuario dueño del bot de Telegram.
     *
     * El {secret} en la URL es sha256(telegram_bot_token), lo que permite
     * identificar al usuario sin exponer el token directamente en la URL.
     * Cada usuario registra su bot token en vera_configs y la URL de webhook
     * que debe configurar en @BotFather es: /webhooks/telegram/{sha256(bot_token)}
     */
    private function findUserByChatContext(int $chatId): ?User // $chatId disponible para logs futuros
    {
        // El secret ya fue validado en verifySecret() y guardado en el request
        $secret = request()->route('secret');

        if (!$secret) {
            return null;
        }

        // Buscar en vera_configs el registro cuyo sha256(telegram_bot_token) coincida
        $configs = \App\Models\VeraConfig::whereNotNull('telegram_bot_token')->get();

        foreach ($configs as $config) {
            if (hash_equals(hash('sha256', $config->telegram_bot_token), $secret)) {
                return $config->user;
            }
        }

        return null;
    }

    /**
     * Verifica que el secret en la URL corresponde a algún bot registrado.
     * En entorno local acepta cualquier secret si no hay usuarios con bot configurado.
     */
    private function verifySecret(string $secret): bool
    {
        // Verificar contra los bots registrados en vera_configs
        $configs = \App\Models\VeraConfig::whereNotNull('telegram_bot_token')->get();

        foreach ($configs as $config) {
            if (hash_equals(hash('sha256', $config->telegram_bot_token), $secret)) {
                return true;
            }
        }

        // Fallback: secret global en .env (útil para desarrollo antes de tener usuarios)
        $globalSecret = config('services.telegram.webhook_secret');
        if ($globalSecret && hash_equals($globalSecret, $secret)) {
            return true;
        }

        return app()->environment('local');
    }
}
