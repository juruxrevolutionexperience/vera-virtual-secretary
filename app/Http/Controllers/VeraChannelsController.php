<?php

namespace App\Http\Controllers;

use App\Models\VeraConfig;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class VeraChannelsController extends Controller
{
    // ─── Telegram Wizard ─────────────────────────────────────────────────────

    public function telegramIndex()
    {
        $user   = Auth::user();
        $config = VeraConfig::where('user_id', $user->id)->firstOrFail();

        $webhookUrl = null;
        $webhookInfo = null;

        if ($config->telegram_bot_token) {
            $webhookUrl = url('/webhooks/telegram/' . hash('sha256', $config->telegram_bot_token));

            // Consultar estado real del webhook en Telegram
            try {
                $response = Http::timeout(5)->get(
                    "https://api.telegram.org/bot{$config->telegram_bot_token}/getWebhookInfo"
                );
                if ($response->ok() && $response->json('ok')) {
                    $webhookInfo = $response->json('result');
                }
            } catch (\Exception $e) {
                Log::warning('Telegram getWebhookInfo falló', ['error' => $e->getMessage()]);
            }
        }

        return view('vera.channels.telegram', compact('config', 'webhookUrl', 'webhookInfo'));
    }

    /**
     * Guarda el token y verifica que es válido llamando a getMe.
     * Devuelve la URL del webhook generada automáticamente.
     */
    public function saveTelegramToken(Request $request)
    {
        $request->validate([
            'telegram_bot_token' => 'required|string|min:20|max:200',
        ]);

        $token = trim($request->telegram_bot_token);

        // Verificar token contra Telegram API antes de guardarlo
        try {
            $response = Http::timeout(8)->get("https://api.telegram.org/bot{$token}/getMe");
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'No se pudo conectar con Telegram. Comprueba tu conexión.',
            ], 422);
        }

        if (!$response->ok() || !$response->json('ok')) {
            return response()->json([
                'success' => false,
                'message' => 'Token inválido. Verifica que lo has copiado correctamente desde @BotFather.',
            ], 422);
        }

        $botInfo = $response->json('result');

        $user   = Auth::user();
        $config = VeraConfig::where('user_id', $user->id)->firstOrFail();
        $config->update(['telegram_bot_token' => $token]);

        $webhookUrl = url('/webhooks/telegram/' . hash('sha256', $token));

        Log::info('Telegram token guardado', [
            'user_id'      => $user->id,
            'bot_username' => $botInfo['username'] ?? null,
        ]);

        return response()->json([
            'success'      => true,
            'webhook_url'  => $webhookUrl,
            'bot_name'     => $botInfo['first_name'] ?? null,
            'bot_username' => '@' . ($botInfo['username'] ?? ''),
        ]);
    }

    /**
     * Llama a setWebhook en la API de Telegram para registrar la URL automáticamente.
     */
    public function registerWebhook()
    {
        $user   = Auth::user();
        $config = VeraConfig::where('user_id', $user->id)->firstOrFail();

        if (!$config->telegram_bot_token) {
            return response()->json(['success' => false, 'message' => 'Primero guarda el token del bot.'], 422);
        }

        $token      = $config->telegram_bot_token;
        $webhookUrl = url('/webhooks/telegram/' . hash('sha256', $token));

        try {
            $response = Http::timeout(10)->post(
                "https://api.telegram.org/bot{$token}/setWebhook",
                [
                    'url'             => $webhookUrl,
                    'allowed_updates' => ['message', 'channel_post'],
                ]
            );
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error de conexión con Telegram: ' . $e->getMessage(),
            ], 422);
        }

        if (!$response->ok() || !$response->json('ok')) {
            $description = $response->json('description') ?? 'Error desconocido';
            return response()->json(['success' => false, 'message' => $description], 422);
        }

        Log::info('Telegram webhook registrado', ['user_id' => $user->id, 'webhook_url' => $webhookUrl]);

        return response()->json([
            'success'     => true,
            'webhook_url' => $webhookUrl,
            'message'     => 'Webhook registrado correctamente.',
        ]);
    }

    /**
     * Elimina el webhook en Telegram y borra el token de vera_configs.
     */
    public function disconnectTelegram()
    {
        $user   = Auth::user();
        $config = VeraConfig::where('user_id', $user->id)->firstOrFail();

        if ($config->telegram_bot_token) {
            try {
                Http::timeout(5)->post(
                    "https://api.telegram.org/bot{$config->telegram_bot_token}/deleteWebhook"
                );
            } catch (\Exception $e) {
                Log::warning('No se pudo eliminar webhook de Telegram', ['error' => $e->getMessage()]);
            }
        }

        $config->update([
            'telegram_bot_token' => null,
            'telegram_chat_id'   => null,
        ]);

        Log::info('Telegram desconectado', ['user_id' => $user->id]);

        return response()->json(['success' => true]);
    }

    // ─── WhatsApp (Meta Cloud API) ───────────────────────────────────────────

    public function whatsappIndex()
    {
        $user   = Auth::user();
        $config = VeraConfig::where('user_id', $user->id)->firstOrFail();
        $webhookUrl   = url('/webhooks/whatsapp');
        $verifyToken  = config('services.whatsapp.verify_token');

        return view('vera.channels.whatsapp', compact('config', 'webhookUrl', 'verifyToken'));
    }

    /**
     * Guarda phone_number_id + access_token y verifica contra Meta antes de guardar
     * (consulta el número en la Graph API). Así confirmamos que los datos son válidos.
     */
    public function saveWhatsApp(Request $request)
    {
        $request->validate([
            'whatsapp_phone_number_id' => 'required|string|max:50',
            'whatsapp_access_token'    => 'required|string|max:1000',
        ]);

        $pnid    = trim($request->whatsapp_phone_number_id);
        $token   = trim($request->whatsapp_access_token);
        $version = config('services.whatsapp.api_version', 'v25.0');

        try {
            $res = Http::withToken($token)->timeout(10)
                ->get("https://graph.facebook.com/{$version}/{$pnid}", ['fields' => 'verified_name,display_phone_number']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'No se pudo conectar con Meta. Revisa tu conexión.'], 422);
        }

        if (!$res->successful()) {
            return response()->json([
                'success' => false,
                'message' => 'Datos inválidos. Revisa el Phone Number ID y el token (¿el token temporal ha caducado?).',
            ], 422);
        }

        $user   = Auth::user();
        $config = VeraConfig::where('user_id', $user->id)->firstOrFail();
        $config->update([
            'whatsapp_phone_number_id' => $pnid,
            'whatsapp_access_token'    => $token,
        ]);

        Log::info('WhatsApp conectado', ['user_id' => $user->id, 'phone_number_id' => $pnid]);

        return response()->json([
            'success'     => true,
            'number'      => $res->json('display_phone_number'),
            'name'        => $res->json('verified_name'),
            'webhook_url' => url('/webhooks/whatsapp'),
        ]);
    }

    public function disconnectWhatsApp()
    {
        $user   = Auth::user();
        $config = VeraConfig::where('user_id', $user->id)->firstOrFail();

        $config->update([
            'whatsapp_phone_number_id' => null,
            'whatsapp_access_token'    => null,
        ]);

        Log::info('WhatsApp desconectado', ['user_id' => $user->id]);

        return response()->json(['success' => true]);
    }
}
