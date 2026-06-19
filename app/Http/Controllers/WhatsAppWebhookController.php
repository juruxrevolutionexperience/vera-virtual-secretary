<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessIncomingMessage;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * WhatsAppWebhookController — Sprint 3
 *
 * Recibe mensajes de WhatsApp Business API (Meta).
 *
 * Dos endpoints:
 * - GET  /webhooks/whatsapp → verificación del webhook por Meta
 * - POST /webhooks/whatsapp → mensajes entrantes
 *
 * Configura en Meta Developer Portal:
 * - Webhook URL: https://tu-dominio.com/webhooks/whatsapp
 * - Verify Token: WHATSAPP_VERIFY_TOKEN (en .env)
 * - Eventos suscritos: messages
 */
class WhatsAppWebhookController extends Controller
{
    /**
     * Verificación del webhook (GET).
     * Meta hace esta petición al configurar el webhook.
     */
    public function verify(Request $request)
    {
        $mode      = $request->query('hub_mode');
        $token     = $request->query('hub_verify_token');
        $challenge = $request->query('hub_challenge');

        if ($mode === 'subscribe' && $token === config('services.whatsapp.verify_token')) {
            Log::info('✅ WhatsApp webhook verificado');
            return response($challenge, 200)->header('Content-Type', 'text/plain');
        }

        Log::warning('❌ WhatsApp webhook verificación fallida', [
            'mode'  => $mode,
            'token' => $token,
        ]);

        return response('Forbidden', 403);
    }

    /**
     * Mensajes entrantes (POST).
     * Meta envía todos los eventos de WhatsApp aquí.
     */
    public function handle(Request $request)
    {
        // Verificar firma HMAC de Meta
        if (!$this->verifySignature($request)) {
            Log::warning('❌ WhatsApp firma inválida', ['ip' => $request->ip()]);
            return response('Unauthorized', 401);
        }

        $payload = $request->json()->all();

        // Confirmar recepción a Meta inmediatamente (debe responder en < 5s)
        // El procesamiento real va al Job en cola
        try {
            $this->dispatchMessages($payload);
        } catch (\Exception $e) {
            Log::error('❌ Error despachando mensaje WhatsApp', ['error' => $e->getMessage()]);
        }

        return response('OK', 200);
    }

    /**
     * Extrae mensajes del payload y despacha un Job por cada uno.
     */
    private function dispatchMessages(array $payload): void
    {
        $entries = $payload['entry'] ?? [];

        foreach ($entries as $entry) {
            $changes = $entry['changes'] ?? [];

            foreach ($changes as $change) {
                if (($change['field'] ?? '') !== 'messages') {
                    continue;
                }

                $value    = $change['value'] ?? [];
                $messages = $value['messages'] ?? [];
                $contacts = $value['contacts'] ?? [];
                $metadata = $value['metadata'] ?? [];

                // ID del número de negocio (para encontrar al usuario)
                $phoneNumberId = $metadata['phone_number_id'] ?? null;

                foreach ($messages as $message) {
                    // Solo procesar mensajes de texto por ahora
                    if (($message['type'] ?? '') !== 'text') {
                        continue;
                    }

                    $from    = $message['from'] ?? null;   // Número del cliente
                    $text    = $message['text']['body'] ?? '';
                    $msgId   = $message['id'] ?? null;

                    if (!$from || !$text) {
                        continue;
                    }

                    // Buscar nombre del contacto si viene en el payload
                    $contactName = null;
                    foreach ($contacts as $contact) {
                        if (($contact['wa_id'] ?? '') === $from) {
                            $contactName = $contact['profile']['name'] ?? null;
                            break;
                        }
                    }

                    // Encontrar el usuario dueño de este número de WhatsApp
                    $user = $this->findUserByPhoneNumberId($phoneNumberId);

                    if (!$user) {
                        Log::warning('WhatsApp: no se encontró usuario para phone_number_id', [
                            'phone_number_id' => $phoneNumberId,
                        ]);
                        continue;
                    }

                    Log::info('📱 Mensaje WhatsApp recibido', [
                        'user_id' => $user->id,
                        'from'    => $from,
                        'length'  => strlen($text),
                    ]);

                    // Despachar Job a la cola
                    ProcessIncomingMessage::dispatchWithDelay(
                        userId:             $user->id,
                        channel:            'whatsapp',
                        message:            $text,
                        contactIdentifier:  $from,
                        contactName:        $contactName,
                        metadata:           ['message_id' => $msgId, 'phone_number_id' => $phoneNumberId]
                    );
                }
            }
        }
    }

    /**
     * Encuentra al usuario dueño del número de WhatsApp Business.
     * TODO Sprint 4: guardar phone_number_id en vera_configs cuando el usuario conecte WhatsApp.
     */
    private function findUserByPhoneNumberId(?string $phoneNumberId): ?User
    {
        if (!$phoneNumberId) {
            return null;
        }

        $config = \App\Models\VeraConfig::where('whatsapp_phone_number_id', $phoneNumberId)->first();

        return $config?->user;
    }

    /**
     * Verifica la firma HMAC-SHA256 de Meta.
     */
    private function verifySignature(Request $request): bool
    {
        $appSecret = config('services.whatsapp.app_secret');

        if (!$appSecret) {
            // Sin secret configurado, aceptar en desarrollo
            return app()->environment('local');
        }

        $signature = $request->header('X-Hub-Signature-256');

        if (!$signature) {
            return false;
        }

        $expectedSignature = 'sha256=' . hash_hmac('sha256', $request->getContent(), $appSecret);

        return hash_equals($expectedSignature, $signature);
    }
}
