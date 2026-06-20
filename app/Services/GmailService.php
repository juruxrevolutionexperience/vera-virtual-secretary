<?php

namespace App\Services;

use App\Models\GmailProcessedMessage;
use App\Models\VeraConfig;
use App\Traits\GoogleOAuthTrait;
use Google\Client as GoogleClient;
use Google\Service\Gmail;
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

        // Query Gmail con margen amplio: la dedupe real la hacemos contra nuestra tabla local
        // gmail_processed_messages, evitando depender del scope restricted gmail.modify
        // para marcar emails como leídos.
        $messages = $gmail->users_messages->listUsersMessages('me', [
            'q'          => 'is:unread in:inbox',
            'maxResults' => max($maxResults * 3, 30),
        ]);

        if (empty($messages->getMessages())) {
            return [];
        }

        $candidateIds = array_map(fn ($m) => $m->getId(), $messages->getMessages());

        $alreadyProcessed = GmailProcessedMessage::where('user_id', $config->user_id)
            ->whereIn('gmail_message_id', $candidateIds)
            ->pluck('gmail_message_id')
            ->all();

        $pendingIds = array_values(array_diff($candidateIds, $alreadyProcessed));
        $pendingIds = array_slice($pendingIds, 0, $maxResults);

        $emails = [];
        foreach ($pendingIds as $id) {
            try {
                $emails[] = $this->parseMessage($gmail, $id);
            } catch (\Exception $e) {
                Log::warning('Gmail: error al parsear mensaje', [
                    'message_id' => $id,
                    'error'      => $e->getMessage(),
                ]);
            }
        }

        return $emails;
    }

    /**
     * Marca un email como procesado en nuestra tabla local — reemplaza el antiguo
     * markAsRead() que requería gmail.modify (scope restricted).
     */
    public function markProcessed(VeraConfig $config, string $messageId): void
    {
        GmailProcessedMessage::firstOrCreate(
            ['user_id' => $config->user_id, 'gmail_message_id' => $messageId],
            ['processed_at' => now()]
        );
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
     * Recorre el árbol MIME de forma RECURSIVA: muchos clientes (Outlook, correos
     * con firma/logo) anidan el cuerpo en multipart/mixed → multipart/alternative →
     * text/plain. Sin recursión el cuerpo salía vacío y Vera solo veía el asunto.
     */
    private function extractBody($payload): string
    {
        // Email simple (cuerpo directo, sin partes)
        $data = $payload->getBody()?->getData();
        if ($data) {
            return $this->decodeBase64($data);
        }

        // Preferimos text/plain en cualquier nivel; si no, text/html (limpiado).
        $plain = $this->findMimePart($payload, 'text/plain');
        if ($plain !== null && trim($plain) !== '') {
            return $plain;
        }

        $html = $this->findMimePart($payload, 'text/html');
        if ($html !== null) {
            return trim(strip_tags($html));
        }

        return '';
    }

    /**
     * Busca recursivamente la primera parte del MIME con el tipo dado y devuelve su
     * contenido decodificado. Null si no existe.
     */
    private function findMimePart($payload, string $mimeType): ?string
    {
        foreach ($payload->getParts() ?? [] as $part) {
            if ($part->getMimeType() === $mimeType) {
                $data = $part->getBody()?->getData();
                if ($data) {
                    return $this->decodeBase64($data);
                }
            }
            // Bajar a sub-partes (multipart anidado)
            $nested = $this->findMimePart($part, $mimeType);
            if ($nested !== null) {
                return $nested;
            }
        }
        return null;
    }

    private function decodeBase64(string $data): string
    {
        return base64_decode(strtr($data, '-_', '+/'));
    }

    // ─── Acciones ─────────────────────────────────────────────────────────────

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

        // Multipart/alternative: parte de texto (fallback de siempre) + parte HTML.
        // El cliente de correo elige la HTML si puede; si no, cae al texto plano.
        $textPart = $replyBody . "\r\n\r\n-- \r\n" . $veraSignature;
        $htmlPart = $this->buildHtmlBody($replyBody, $veraSignature, $config->business_name ?? 'Vera');
        $boundary = 'vera_' . bin2hex(random_bytes(8));

        $rawMessage = implode("\r\n", [
            "To: {$toEmail}",
            "Subject: {$encodedSubject}",
            "MIME-Version: 1.0",
            "Content-Type: multipart/alternative; boundary=\"{$boundary}\"",
            "",
            "--{$boundary}",
            "Content-Type: text/plain; charset=utf-8",
            "Content-Transfer-Encoding: 8bit",
            "",
            $textPart,
            "",
            "--{$boundary}",
            "Content-Type: text/html; charset=utf-8",
            "Content-Transfer-Encoding: 8bit",
            "",
            $htmlPart,
            "",
            "--{$boundary}--",
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

    /**
     * Convierte el cuerpo de texto de Vera en HTML para el email.
     * - Escapa HTML y respeta saltos de línea.
     * - Si encuentra un enlace de reserva (/reservar/TOKEN), lo extrae del cuerpo
     *   y lo pinta como un BOTÓN al final, con el dominio visible debajo
     *   (transparencia anti-phishing). El resto de URLs se vuelven enlaces normales.
     */
    private function buildHtmlBody(string $text, string $signature, string $businessName = 'Vera'): string
    {
        // Extraer el enlace de acción (reserva o cancelación) para renderizarlo como botón.
        $actionUrl   = null;
        $buttonLabel = '';
        if (preg_match('#https?://[^\s<>"]+/reservar/[A-Za-z0-9]+#', $text, $m)) {
            $actionUrl   = $m[0];
            $buttonLabel = 'Ver agenda y reservar';
        } elseif (preg_match('#https?://[^\s<>"]+/cancelar/[A-Za-z0-9]+#', $text, $m)) {
            $actionUrl   = $m[0];
            $buttonLabel = 'Cancelar mi cita';
        } elseif (preg_match('#https?://[^\s<>"]*(?:google\.[a-z.]+/maps|maps\.google|maps\.app\.goo\.gl|goo\.gl/maps)[^\s<>"]*#i', $text, $m)) {
            $actionUrl   = $m[0];
            $buttonLabel = '📍 Cómo llegar';
        }
        if ($actionUrl) {
            // Quitar la URL del cuerpo (y la etiqueta tipo "...aquí:" justo antes, si la hay)
            $text = trim(preg_replace('#[^\n]*' . preg_quote($actionUrl, '#') . '#', '', $text));
        }

        $escaped = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
        // Autolink del resto de URLs (las que no sean de reserva)
        $escaped = preg_replace(
            '#(https?://[^\s<>"]+)#',
            '<a href="$1" style="color:#7c3aed;">$1</a>',
            $escaped
        );
        $bodyHtml = nl2br($escaped);

        $button = '';
        if ($actionUrl) {
            $safeUrl   = htmlspecialchars($actionUrl, ENT_QUOTES, 'UTF-8');
            $safeLabel = htmlspecialchars($buttonLabel, ENT_QUOTES, 'UTF-8');
            $button = '
            <table role="presentation" cellspacing="0" cellpadding="0" border="0" style="margin:20px 0;">
                <tr><td style="border-radius:8px;background:#7c3aed;">
                    <a href="' . $safeUrl . '" style="display:inline-block;padding:13px 30px;color:#ffffff;text-decoration:none;font-size:15px;font-weight:600;border-radius:8px;">
                        ' . $safeLabel . '
                    </a>
                </td></tr>
            </table>
            <p style="margin:0 0 16px 0;font-size:12px;color:#9ca3af;">' . $safeUrl . '</p>';
        }

        $sig  = nl2br(htmlspecialchars($signature, ENT_QUOTES, 'UTF-8'));
        $name = htmlspecialchars($businessName, ENT_QUOTES, 'UTF-8');

        // Tarjeta centrada (mismo estilo que los emails transaccionales de Vera):
        // fondo gris + tarjeta blanca + cabecera con el nombre del negocio + firma
        // + pie "Gestionado por Vera". El texto de Vera va alineado a la izquierda
        // dentro de la tarjeta.
        return '<!DOCTYPE html>
<html lang="es"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head>
<body style="margin:0;padding:0;background:#f4f4f5;font-family:-apple-system,BlinkMacSystemFont,\'Segoe UI\',Roboto,Arial,sans-serif;color:#1f2937;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background:#f4f4f5;padding:32px 16px;">
        <tr><td align="center">
            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="max-width:560px;background:#ffffff;border-radius:12px;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,0.06);">
                <tr><td style="background:#18181b;padding:20px 32px;">
                    <p style="margin:0;font-size:13px;font-weight:600;color:#a1a1aa;letter-spacing:0.05em;text-transform:uppercase;">' . $name . '</p>
                </td></tr>
                <tr><td style="padding:28px 32px;font-size:15px;line-height:1.6;color:#1f2937;">
                    <p style="margin:0 0 12px 0;">' . $bodyHtml . '</p>'
                    . $button .
                    '<p style="margin:24px 0 0 0;color:#6b7280;font-size:14px;">-- <br>' . $sig . '</p>
                </td></tr>
            </table>
            <p style="margin:20px 0 0 0;font-size:12px;color:#a1a1aa;">Gestionado por Vera · Jurux</p>
        </td></tr>
    </table>
</body></html>';
    }
}
