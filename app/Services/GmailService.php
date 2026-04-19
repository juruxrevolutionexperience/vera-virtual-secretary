<?php

namespace App\Services;

use App\Models\VeraConfig;
use App\Traits\GoogleOAuthTrait;
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
    use GoogleOAuthTrait;

    private GoogleClient $client;

    public function __construct()
    {
        $this->client = new GoogleClient();
        $this->client->setClientId(config('services.google.client_id'));
        $this->client->setClientSecret(config('services.google.client_secret'));
        $this->client->setAccessType('offline');
    }

    // ─── Autenticación ────────────────────────────────────────────────────────

    private function getGmailService(VeraConfig $config): Gmail
    {
        $this->refreshGoogleToken($this->client, $config);
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

        $from             = $headers->firstWhere('name', 'From')?->getValue()             ?? '';
        $subject          = $headers->firstWhere('name', 'Subject')?->getValue()          ?? '(sin asunto)';
        $date             = $headers->firstWhere('name', 'Date')?->getValue()             ?? '';
        $hasUnsubscribe   = $headers->firstWhere('name', 'List-Unsubscribe') !== null;

        $body = $this->extractBody($msg->getPayload());

        return [
            'id'                   => $messageId,
            'thread_id'            => $msg->getThreadId(),
            'from'                 => $from,
            'subject'              => $subject,
            'date'                 => $date,
            'body'                 => $body,
            'snippet'              => $msg->getSnippet(),
            'has_list_unsubscribe' => $hasUnsubscribe,
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

        // Fallback: primer parte disponible — puede ser HTML, lo limpiamos
        foreach ($payload->getParts() ?? [] as $part) {
            $data = $part->getBody()?->getData();
            if ($data) return strip_tags($this->decodeBase64($data));
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

        // Usar regex para quitar el prefijo "Re:" solo si ya existe — ltrim() trata el segundo
        // argumento como máscara de caracteres, lo que rompe asuntos como "Reunión" (→ "ón").
        $base    = preg_replace('/^(Re:\s*)+/i', '', $originalEmail['subject']);
        $subject = 'Re: ' . $base;

        // Codificar el asunto en RFC 2047 (Base64) para preservar tildes, ñ, etc.
        // Sin esto, los bytes UTF-8 en el header se interpretan como Latin-1 → "reuniÃƒÂ³n"
        $encodedSubject = mb_detect_encoding($subject, 'ASCII', true)
            ? $subject
            : '=?UTF-8?B?' . base64_encode($subject) . '?=';

        $rawMessage = implode("\r\n", [
            "To: {$toEmail}",
            "Subject: {$encodedSubject}",
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
