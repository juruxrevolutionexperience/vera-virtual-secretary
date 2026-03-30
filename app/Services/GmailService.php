<?php

namespace App\Services;

use App\Models\VeraConfig;
use Google\Client as GoogleClient;
use Google\Service\Gmail;
use Google\Service\Gmail\ModifyMessageRequest;
use Illuminate\Support\Facades\Log;

/**
 * GmailService
 *
 * Lee emails del inbox del usuario usando la Gmail API.
 * Comparte el mismo token OAuth que GoogleCalendarService.
 *
 * Uso principal: detectar solicitudes de reunión entrantes.
 */
class GmailService
{
    private GoogleClient $client;

    public function __construct()
    {
        $this->client = new GoogleClient();
        $this->client->setClientId(config('services.google.client_id'));
        $this->client->setClientSecret(config('services.google.client_secret'));
        $this->client->setAccessType('offline');
    }

    // ─── Autenticación ────────────────────────────────────────────────────────

    private function authenticate(VeraConfig $config): void
    {
        $this->client->setAccessToken([
            'access_token'  => $config->google_access_token,
            'refresh_token' => $config->google_refresh_token,
            'expires_in'    => $config->google_token_expires_at
                ? now()->diffInSeconds($config->google_token_expires_at, false)
                : 0,
        ]);

        if ($this->client->isAccessTokenExpired()) {
            if (!$config->google_refresh_token) {
                throw new \RuntimeException('Gmail: no hay refresh_token. El usuario debe reconectar su cuenta.');
            }

            $newToken = $this->client->fetchAccessTokenWithRefreshToken($config->google_refresh_token);

            $config->update([
                'google_access_token'     => $newToken['access_token'],
                'google_token_expires_at' => now()->addSeconds($newToken['expires_in'] ?? 3600),
            ]);

            Log::info('🔄 Gmail token refrescado', ['user_id' => $config->user_id]);
        }
    }

    private function getGmailService(VeraConfig $config): Gmail
    {
        $this->authenticate($config);
        return new Gmail($this->client);
    }

    // ─── Lectura de emails ────────────────────────────────────────────────────

    /**
     * Obtiene emails no leídos de la bandeja de entrada.
     * Devuelve array de mensajes con: id, from, subject, body, date.
     *
     * @param  VeraConfig $config
     * @param  int        $maxResults  Máximo de emails a leer por llamada
     * @return array
     */
    public function getUnreadEmails(VeraConfig $config, int $maxResults = 10): array
    {
        $gmail = $this->getGmailService($config);

        $messages = $gmail->users_messages->listUsersMessages('me', [
            'q'          => 'is:unread in:inbox',
            'maxResults' => $maxResults,
        ]);

        if (empty($messages->getMessages())) {
            return [];
        }

        $emails = [];
        foreach ($messages->getMessages() as $msg) {
            try {
                $emails[] = $this->parseMessage($gmail, $msg->getId());
            } catch (\Exception $e) {
                Log::warning('Gmail: error al parsear mensaje', [
                    'message_id' => $msg->getId(),
                    'error'      => $e->getMessage(),
                ]);
            }
        }

        return $emails;
    }

    /**
     * Parsea un mensaje de Gmail a un array legible.
     */
    private function parseMessage(Gmail $gmail, string $messageId): array
    {
        $msg     = $gmail->users_messages->get('me', $messageId, ['format' => 'full']);
        $headers = collect($msg->getPayload()->getHeaders());

        $from    = $headers->firstWhere('name', 'From')?->getValue()  ?? '';
        $subject = $headers->firstWhere('name', 'Subject')?->getValue() ?? '(sin asunto)';
        $date    = $headers->firstWhere('name', 'Date')?->getValue()  ?? '';

        $body = $this->extractBody($msg->getPayload());

        return [
            'id'      => $messageId,
            'from'    => $from,
            'subject' => $subject,
            'date'    => $date,
            'body'    => $body,
            'snippet' => $msg->getSnippet(),
        ];
    }

    /**
     * Extrae el texto del cuerpo del email (prefiere texto plano sobre HTML).
     */
    private function extractBody($payload): string
    {
        // Email simple (sin partes)
        $data = $payload->getBody()?->getData();
        if ($data) {
            return $this->decodeBase64($data);
        }

        // Email multiparte
        foreach ($payload->getParts() ?? [] as $part) {
            if ($part->getMimeType() === 'text/plain') {
                $data = $part->getBody()?->getData();
                if ($data) return $this->decodeBase64($data);
            }
        }

        // Fallback: primer parte disponible
        foreach ($payload->getParts() ?? [] as $part) {
            $data = $part->getBody()?->getData();
            if ($data) return $this->decodeBase64($data);
        }

        return '';
    }

    private function decodeBase64(string $data): string
    {
        return base64_decode(strtr($data, '-_', '+/'));
    }

    // ─── Acciones ─────────────────────────────────────────────────────────────

    /**
     * Marca un email como leído (quita la etiqueta UNREAD).
     */
    public function markAsRead(VeraConfig $config, string $messageId): void
    {
        $gmail = $this->getGmailService($config);

        $mods = new ModifyMessageRequest();
        $mods->setRemoveLabelIds(['UNREAD']);
        $gmail->users_messages->modify('me', $messageId, $mods);
    }

    /**
     * Responde a un email con el texto dado.
     * Construye el hilo de respuesta correcto (In-Reply-To + References).
     */
    public function replyToEmail(VeraConfig $config, array $originalEmail, string $replyBody, string $veraSignature): void
    {
        $gmail = $this->getGmailService($config);

        // Extraer email del campo From: "Nombre <email@ejemplo.com>"
        preg_match('/<(.+?)>/', $originalEmail['from'], $matches);
        $toEmail = $matches[1] ?? $originalEmail['from'];

        $subject = 'Re: ' . ltrim($originalEmail['subject'], 'Re: ');

        $rawMessage = implode("\r\n", [
            "To: {$toEmail}",
            "Subject: {$subject}",
            "Content-Type: text/plain; charset=utf-8",
            "",
            $replyBody,
            "",
            "-- ",
            $veraSignature,
        ]);

        $encoded = base64_encode($rawMessage);
        $encoded = strtr($encoded, '+/', '-_'); // URL-safe base64

        $message = new Gmail\Message();
        $message->setRaw($encoded);
        $message->setThreadId($originalEmail['thread_id'] ?? null);

        $gmail->users_messages->send('me', $message);

        Log::info('📧 Email de respuesta enviado', [
            'to'      => $toEmail,
            'subject' => $subject,
        ]);
    }
}
