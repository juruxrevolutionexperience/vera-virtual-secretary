<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Jobs\IncrementalSyncEmailsJob;
use App\Models\EmailAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * GmailWebhookController
 *
 * Recibe las notificaciones push de Google PubSub cuando llega un email.
 * Flujo: Gmail → PubSub → POST /webhooks/gmail → dispatch IncrementalSyncEmailsJob
 *
 * En desarrollo este endpoint no recibe nada (no hay URL pública).
 * En producción se activa automáticamente al conectar Gmail.
 */
class GmailWebhookController extends Controller
{
    public function handle(Request $request)
    {
        // Verificar token secreto en la query string
        // Google PubSub envía: POST /webhooks/gmail?token=TU_SECRET
        $token = $request->query('token');

        if ($token !== config('vera.gmail_webhook_secret')) {
            Log::warning('⚠️ Gmail webhook: token inválido', [
                'ip'    => $request->ip(),
                'token' => substr($token ?? '', 0, 8) . '...',
            ]);
            // Devolvemos 200 igualmente para que PubSub no reintente indefinidamente
            return response()->json(['ok' => false], 200);
        }

        // PubSub envía: {"message": {"data": "BASE64", "messageId": "..."}, "subscription": "..."}
        $message = $request->input('message');

        if (!$message || !isset($message['data'])) {
            return response()->json(['ok' => true]);
        }

        // Decodificar el payload de Gmail
        // Contiene: {"emailAddress": "user@gmail.com", "historyId": "12345"}
        $payload = json_decode(base64_decode($message['data']), true);

        $emailAddress = $payload['emailAddress'] ?? null;
        $historyId    = $payload['historyId'] ?? null;

        if (!$emailAddress) {
            Log::warning('⚠️ Gmail webhook: payload sin emailAddress');
            return response()->json(['ok' => true]);
        }

        Log::info('📬 Gmail webhook recibido', [
            'email'      => $emailAddress,
            'history_id' => $historyId,
        ]);

        // Buscar la cuenta y despachar sincronización incremental
        $account = EmailAccount::where('email_address', $emailAddress)
            ->where('sync_enabled', true)
            ->first();

        if (!$account) {
            Log::warning('⚠️ Gmail webhook: cuenta no encontrada', ['email' => $emailAddress]);
            return response()->json(['ok' => true]);
        }

        // Actualizar history_id y despachar sync incremental
        if ($historyId) {
            $account->update(['last_history_id' => $historyId]);
        }

        IncrementalSyncEmailsJob::dispatch($account);

        Log::info('✅ Gmail webhook: sync incremental despachado', [
            'account_id' => $account->id,
            'email'      => $emailAddress,
        ]);

        // PubSub espera HTTP 200 para confirmar que recibimos la notificación
        return response()->json(['ok' => true]);
    }
}
