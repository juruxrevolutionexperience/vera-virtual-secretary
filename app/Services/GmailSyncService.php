<?php

namespace App\Services;

use App\Models\EmailAccount;
use App\Models\Email;
use App\Models\SyncLog;
use Google\Client as GoogleClient;
use Google\Service\Gmail;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class GmailSyncService
{
    protected GoogleClient $client;
    protected ?Gmail $gmail = null;

    public function __construct()
    {
        $this->client = new GoogleClient();
        $this->client->setClientId(config('services.google.client_id'));
        $this->client->setClientSecret(config('services.google.client_secret'));
        $this->client->setRedirectUri(config('services.google.redirect'));
        $this->client->addScope(Gmail::GMAIL_READONLY);
        $this->client->addScope(Gmail::GMAIL_SEND);
        $this->client->addScope(Gmail::GMAIL_MODIFY);
        $this->client->addScope(Gmail::GMAIL_LABELS);
    }

    /**
     * Inicializar cliente con credenciales de la cuenta
     */
    protected function initializeClient(EmailAccount $account): void
    {
        $accessToken = $account->access_token;
        $refreshToken = $account->refresh_token;

        Log::info('Tokens retrieved', [
            'has_access' => !empty($accessToken),
            'has_refresh' => !empty($refreshToken),
            'access_len' => strlen($accessToken ?? ''),
            'refresh_len' => strlen($refreshToken ?? ''),
        ]);

        $tokenArray = [
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
            'expires_in' => $account->token_expires_at ? $account->token_expires_at->diffInSeconds(now()) : 3600,
        ];

        Log::info('Setting access token', ['token_array' => $tokenArray]);

        $this->client->setAccessToken($tokenArray);

        // Refrescar token si está expirado
        if ($this->client->isAccessTokenExpired() && $account->refresh_token) {
            $newToken = $this->client->fetchAccessTokenWithRefreshToken($account->refresh_token);

            if (isset($newToken['access_token'])) {
                $account->update([
                    'access_token' => $newToken['access_token'],
                    'token_expires_at' => now()->addSeconds($newToken['expires_in'] ?? 3600),
                ]);
            }
        }

        $this->gmail = new Gmail($this->client);
    }

    /**
     * Sincronización inicial completa
     */
    public function initialSync(EmailAccount $account): void
    {
        Log::info('📧 Starting Gmail initial sync', ['account_id' => $account->id]);

        $this->initializeClient($account);

        // Primero, obtener el conteo total aproximado
        $this->getInitialEmailCount($account);

        $pageToken = null;
        $totalProcessed = 0;
        $totalCreated = 0;
        $totalUpdated = 0;
        $totalFailed = 0;

        do {
            $params = [
                'maxResults' => 50, // Reducido de 100 a 50 para evitar timeouts
                'q' => 'in:inbox', // Solo inbox para empezar
            ];

            if ($pageToken) {
                $params['pageToken'] = $pageToken;
            }

            Log::info('Fetching page from Gmail', [
                'params' => $params,
                'progress' => $totalProcessed,
            ]);

            $messagesResponse = $this->gmail->users_messages->listUsersMessages('me', $params);
            $messages = $messagesResponse->getMessages();

            if (empty($messages)) {
                Log::info('No more messages, breaking loop');
                break;
            }

            foreach ($messages as $message) {
                try {
                    $fullMessage = $this->gmail->users_messages->get('me', $message->getId(), [
                        'format' => 'full',
                    ]);

                    $result = $this->processGmailMessage($fullMessage, $account);

                    if ($result['action'] === 'created') {
                        $totalCreated++;
                        // NO disparar Vera durante initial sync - se hace batch al final
                    } elseif ($result['action'] === 'updated') {
                        $totalUpdated++;
                    }

                    $totalProcessed++;

                    // Actualizar progreso cada 10 emails
                    if ($totalProcessed % 10 === 0) {
                        $account->update([
                            'synced_emails_count' => $totalProcessed,
                        ]);

                        Log::info('Progress update', [
                            'processed' => $totalProcessed,
                            'expected' => $account->total_expected_emails,
                            'progress_pct' => $account->total_expected_emails > 0
                                ? round(($totalProcessed / $account->total_expected_emails) * 100, 2)
                                : 0,
                        ]);
                    }

                } catch (\Exception $e) {
                    $totalFailed++;
                    Log::error('Error processing message', [
                        'message_id' => $message->getId(),
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            $pageToken = $messagesResponse->getNextPageToken();

        } while ($pageToken);

        // Actualización final
        $account->update([
            'synced_emails_count' => $totalProcessed,
            'total_emails_synced' => $totalProcessed,
        ]);

        Log::info('✅ Initial sync completed', [
            'total_processed' => $totalProcessed,
            'created' => $totalCreated,
            'updated' => $totalUpdated,
            'failed' => $totalFailed,
        ]);

        // Guardar last_history_id para futuras sincronizaciones incrementales
        try {
            $profile = $this->gmail->users->getProfile('me');
            $account->update([
                'last_history_id' => $profile->getHistoryId(),
            ]);
            Log::info('✅ History ID saved for incremental sync', [
                'history_id' => $profile->getHistoryId(),
            ]);
        } catch (\Exception $e) {
            Log::warning('Could not save history ID', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Sincronización incremental usando Gmail History API
     */
    public function incrementalSync(EmailAccount $account): void
    {
        Log::info('🔄 Starting Gmail incremental sync', ['account_id' => $account->id]);

        $this->initializeClient($account);

        // Si no tenemos last_history_id, obtenerlo y guardarlo para la próxima vez
        if (!$account->last_history_id) {
            Log::info('⚠️ No last_history_id found, obtaining current history ID');
            try {
                $profile = $this->gmail->users->getProfile('me');
                $account->update([
                    'last_history_id' => $profile->getHistoryId(),
                ]);
                Log::info('✅ History ID saved for next incremental sync', [
                    'history_id' => $profile->getHistoryId(),
                ]);
                return; // No hay nada que sincronizar aún
            } catch (\Exception $e) {
                Log::error('Failed to get history ID', ['error' => $e->getMessage()]);
                return;
            }
        }

        $totalCreated = 0;
        $totalUpdated = 0;
        $totalFailed = 0;
        $pageToken = null;
        $currentHistoryId = $account->last_history_id;

        try {
            do {
                $params = [
                    'startHistoryId' => $account->last_history_id,
                    'historyTypes' => ['messageAdded'], // Solo emails nuevos
                    'labelId' => 'INBOX', // Solo inbox
                ];

                if ($pageToken) {
                    $params['pageToken'] = $pageToken;
                }

                Log::info('Fetching history from Gmail', [
                    'start_history_id' => $account->last_history_id,
                    'params' => $params,
                ]);

                $historyResponse = $this->gmail->users_history->listUsersHistory('me', $params);
                $histories = $historyResponse->getHistory();

                if (empty($histories)) {
                    Log::info('✅ No new emails since last sync');
                    break;
                }

                foreach ($histories as $history) {
                    // Actualizar el historyId más reciente
                    if ($history->getId() > $currentHistoryId) {
                        $currentHistoryId = $history->getId();
                    }

                    // Procesar solo mensajes agregados
                    $messagesAdded = $history->getMessagesAdded();
                    if (empty($messagesAdded)) {
                        continue;
                    }

                    foreach ($messagesAdded as $messageAdded) {
                        try {
                            $message = $messageAdded->getMessage();

                            // Solo procesar si es del INBOX
                            $labels = $message->getLabelIds() ?? [];
                            if (!in_array('INBOX', $labels)) {
                                continue;
                            }

                            // Obtener mensaje completo
                            $fullMessage = $this->gmail->users_messages->get('me', $message->getId(), [
                                'format' => 'full',
                            ]);

                            // Procesar y guardar email
                            $result = $this->processGmailMessage($fullMessage, $account);

                            if ($result['action'] === 'created') {
                                $totalCreated++;
                                Log::info('📧 New email received', [
                                    'subject' => $result['email']->subject,
                                    'from' => $result['email']->from_email,
                                ]);

                                // 🤖 Disparar Job de Vera para analizar el email automáticamente
                                \App\Jobs\ProcessEmailWithVera::dispatch($result['email']);
                            } elseif ($result['action'] === 'updated') {
                                $totalUpdated++;
                            }

                        } catch (\Exception $e) {
                            $totalFailed++;
                            Log::error('Error processing message from history', [
                                'message_id' => $message->getId(),
                                'error' => $e->getMessage(),
                            ]);
                        }
                    }
                }

                $pageToken = $historyResponse->getNextPageToken();

            } while ($pageToken);

            // Actualizar last_history_id con el más reciente
            $account->update([
                'last_history_id' => $currentHistoryId,
                'synced_emails_count' => Email::where('email_account_id', $account->id)->count(),
            ]);

            Log::info('✅ Incremental sync completed', [
                'new_emails' => $totalCreated,
                'updated' => $totalUpdated,
                'failed' => $totalFailed,
                'new_history_id' => $currentHistoryId,
            ]);

        } catch (\Google\Service\Exception $e) {
            // Error 404 significa que el historyId es muy antiguo
            if ($e->getCode() === 404) {
                Log::warning('⚠️ History ID too old, resetting to current', [
                    'old_history_id' => $account->last_history_id,
                ]);

                // Obtener nuevo historyId
                $profile = $this->gmail->users->getProfile('me');
                $account->update([
                    'last_history_id' => $profile->getHistoryId(),
                ]);
            } else {
                throw $e;
            }
        }
    }

    /**
     * Obtener conteo REAL de emails en inbox
     */
    protected function getInitialEmailCount(EmailAccount $account): void
    {
        try {
            // Obtener el conteo EXACTO de mensajes en INBOX desde la etiqueta
            $label = $this->gmail->users_labels->get('me', 'INBOX');
            $realInboxCount = $label->getMessagesTotal();

            $account->update([
                'total_expected_emails' => $realInboxCount,
            ]);

            Log::info('Email count obtained from INBOX label', [
                'inbox_count' => $realInboxCount,
            ]);

        } catch (\Exception $e) {
            Log::warning('Could not get email count', ['error' => $e->getMessage()]);
            $account->update(['total_expected_emails' => 0]);
        }
    }

    /**
     * Sincronizar emails de Gmail hacia Vera
     */
    public function syncInbound(EmailAccount $account, string $syncType = 'incremental'): SyncLog
    {
        $syncLog = SyncLog::create([
            'email_account_id' => $account->id,
            'sync_type' => $syncType,
            'direction' => 'inbound',
            'status' => 'success', // Se actualizará al finalizar
            'started_at' => now(),
        ]);

        try {
            Log::info('Initializing Gmail client', ['account_id' => $account->id]);
            $this->initializeClient($account);

            $query = $this->buildQuery($account, $syncType);
            Log::info('Gmail query built', ['query' => $query]);
            $pageToken = null;
            $emailsFetched = 0;
            $emailsCreated = 0;
            $emailsUpdated = 0;
            $emailsFailed = 0;

            do {
                // Obtener lista de mensajes
                $params = [
                    'maxResults' => 100,
                    'q' => $query,
                ];

                if ($pageToken) {
                    $params['pageToken'] = $pageToken;
                }

                Log::info('Fetching messages from Gmail', ['params' => $params]);
                $messagesResponse = $this->gmail->users_messages->listUsersMessages('me', $params);
                $messages = $messagesResponse->getMessages();

                Log::info('Messages retrieved', ['count' => count($messages ?? [])]);

                if (empty($messages)) {
                    Log::info('No messages found, breaking loop');
                    break;
                }

                foreach ($messages as $message) {
                    try {
                        $emailsFetched++;

                        // Obtener mensaje completo
                        $fullMessage = $this->gmail->users_messages->get('me', $message->getId(), [
                            'format' => 'full',
                        ]);

                        // Procesar y guardar email
                        $result = $this->processGmailMessage($fullMessage, $account);

                        if ($result['action'] === 'created') {
                            $emailsCreated++;
                        } elseif ($result['action'] === 'updated') {
                            $emailsUpdated++;
                        }
                    } catch (\Exception $e) {
                        $emailsFailed++;
                        Log::error('Error processing Gmail message', [
                            'account_id' => $account->id,
                            'message_id' => $message->getId(),
                            'error' => $e->getMessage(),
                        ]);
                    }
                }

                $pageToken = $messagesResponse->getNextPageToken();
            } while ($pageToken);

            // Actualizar registro de sincronización
            $syncLog->update([
                'status' => 'success',
                'emails_fetched' => $emailsFetched,
                'emails_created' => $emailsCreated,
                'emails_updated' => $emailsUpdated,
                'emails_failed' => $emailsFailed,
                'completed_at' => now(),
            ]);
            $syncLog->calculateDuration();

            // Actualizar última sincronización de la cuenta
            $account->update([
                'last_sync_at' => now(),
                'sync_status' => 'active',
            ]);

        } catch (\Exception $e) {
            $syncLog->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'error_details' => [
                    'exception' => get_class($e),
                    'trace' => $e->getTraceAsString(),
                ],
                'completed_at' => now(),
            ]);
            $syncLog->calculateDuration();

            $account->update(['sync_status' => 'error']);

            Log::error('Gmail sync failed', [
                'account_id' => $account->id,
                'error' => $e->getMessage(),
            ]);
        }

        return $syncLog;
    }

    /**
     * Construir query de búsqueda según el tipo de sincronización
     */
    protected function buildQuery(EmailAccount $account, string $syncType): string
    {
        $query = [];

        // Sincronización incremental: solo nuevos desde última sync
        if ($syncType === 'incremental' && $account->last_sync_at) {
            $afterDate = $account->last_sync_at->format('Y/m/d');
            $query[] = "after:{$afterDate}";
        }

        // Sincronización completa: últimos 30 días por defecto
        if ($syncType === 'full') {
            if ($account->sync_from_date) {
                $fromDate = Carbon::parse($account->sync_from_date)->format('Y/m/d');
                $query[] = "after:{$fromDate}";
            } else {
                // Últimos 30 días por defecto
                $fromDate = Carbon::now()->subDays(30)->format('Y/m/d');
                $query[] = "after:{$fromDate}";
            }
        }

        // Filtros adicionales según configuración
        if ($account->sync_folders && is_array($account->sync_folders)) {
            $labels = array_map(function ($folder) {
                return "label:{$folder}";
            }, $account->sync_folders);
            $query[] = '(' . implode(' OR ', $labels) . ')';
        }

        return implode(' ', $query);
    }

    /**
     * Procesar mensaje de Gmail y guardar en BD
     */
    protected function processGmailMessage($gmailMessage, EmailAccount $account): array
    {
        $headers = $this->parseHeaders($gmailMessage->getPayload()->getHeaders());
        $messageId = $gmailMessage->getId();
        $threadId = $gmailMessage->getThreadId();

        // Extraer contenido del email
        $body = $this->extractBody($gmailMessage->getPayload());

        // Buscar si ya existe
        $email = Email::where('provider_message_id', $messageId)->first();

        $emailData = [
            'user_id' => $account->user_id,
            'email_account_id' => $account->id,
            'provider' => 'gmail',
            'provider_message_id' => $messageId,
            'provider_thread_id' => $threadId,
            'subject' => $headers['subject'] ?? '(Sin asunto)',
            'from_email' => $this->extractEmail($headers['from'] ?? ''),
            'from_name' => $this->extractName($headers['from'] ?? ''),
            'to_email' => $this->extractEmail($headers['to'] ?? ''),
            'to_name' => $this->extractName($headers['to'] ?? ''),
            'cc' => $headers['cc'] ?? null,
            'bcc' => $headers['bcc'] ?? null,
            'body_text' => $body['text'] ?? null,
            'body_html' => $body['html'] ?? null,
            'received_at' => $this->parseDate($headers['date'] ?? null),
            'is_read' => !in_array('UNREAD', $gmailMessage->getLabelIds() ?? []),
            'is_starred' => in_array('STARRED', $gmailMessage->getLabelIds() ?? []),
            'category' => $this->determineCategory($gmailMessage->getLabelIds() ?? []),
            'status' => 'received',
            'metadata' => [
                'labels' => $gmailMessage->getLabelIds(),
                'snippet' => $gmailMessage->getSnippet(),
                'size' => $gmailMessage->getSizeEstimate(),
            ],
        ];

        if ($email) {
            $email->update($emailData);
            return ['action' => 'updated', 'email' => $email];
        } else {
            $email = Email::create($emailData);

            // Disparar procesamiento con Vera para emails nuevos
            \App\Jobs\ProcessEmailWithVera::dispatch($email);

            return ['action' => 'created', 'email' => $email];
        }
    }

    /**
     * Parsear headers del mensaje
     */
    protected function parseHeaders(array $headers): array
    {
        $parsed = [];
        foreach ($headers as $header) {
            $parsed[strtolower($header->getName())] = $header->getValue();
        }
        return $parsed;
    }

    /**
     * Extraer cuerpo del mensaje
     */
    protected function extractBody($payload): array
    {
        $body = ['text' => null, 'html' => null];

        if ($payload->getBody()->getData()) {
            $mimeType = $payload->getMimeType();
            $decodedBody = base64_decode(strtr($payload->getBody()->getData(), '-_', '+/'));

            if ($mimeType === 'text/plain') {
                $body['text'] = $decodedBody;
            } elseif ($mimeType === 'text/html') {
                $body['html'] = $decodedBody;
            }
        }

        // Manejar partes multipart
        if ($payload->getParts()) {
            foreach ($payload->getParts() as $part) {
                $partBody = $this->extractBody($part);
                if ($partBody['text'])
                    $body['text'] = $partBody['text'];
                if ($partBody['html'])
                    $body['html'] = $partBody['html'];
            }
        }

        return $body;
    }

    /**
     * Extraer email de un string "Name <email@domain.com>"
     */
    protected function extractEmail(string $emailString): string
    {
        if (preg_match('/<(.+?)>/', $emailString, $matches)) {
            return $matches[1];
        }
        return trim($emailString);
    }

    /**
     * Extraer nombre de un string "Name <email@domain.com>"
     */
    protected function extractName(string $emailString): ?string
    {
        if (preg_match('/^(.+?)\s*</', $emailString, $matches)) {
            return trim($matches[1], '" ');
        }
        return null;
    }

    /**
     * Parsear fecha del header
     */
    protected function parseDate(?string $dateString): Carbon
    {
        if (!$dateString) {
            return now();
        }

        try {
            return Carbon::parse($dateString);
        } catch (\Exception $e) {
            return now();
        }
    }

    /**
     * Determinar categoría basada en labels de Gmail
     */
    protected function determineCategory(array $labels): string
    {
        if (in_array('CATEGORY_PROMOTIONS', $labels))
            return 'promotions';
        if (in_array('CATEGORY_SOCIAL', $labels))
            return 'social';
        if (in_array('CATEGORY_UPDATES', $labels))
            return 'updates';
        if (in_array('CATEGORY_FORUMS', $labels))
            return 'forums';
        if (in_array('INBOX', $labels))
            return 'primary';
        if (in_array('SPAM', $labels))
            return 'spam';
        if (in_array('TRASH', $labels))
            return 'trash';
        return 'primary';
    }

    /**
     * Marcar email como leído en Gmail
     */
    public function markAsRead(EmailAccount $account, Email $email): bool
    {
        try {
            $this->initializeClient($account);

            $mods = new Gmail\ModifyMessageRequest();
            $mods->setRemoveLabelIds(['UNREAD']);

            $this->gmail->users_messages->modify('me', $email->provider_message_id, $mods);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to mark Gmail message as read', [
                'email_id' => $email->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Archivar email en Gmail
     */
    public function archive(EmailAccount $account, Email $email): bool
    {
        try {
            $this->initializeClient($account);

            $mods = new Gmail\ModifyMessageRequest();
            $mods->setRemoveLabelIds(['INBOX']);

            $this->gmail->users_messages->modify('me', $email->provider_message_id, $mods);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to archive Gmail message', [
                'email_id' => $email->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Enviar email desde Gmail
     */
    public function sendEmail(EmailAccount $account, array $emailData): ?Email
    {
        try {
            $this->initializeClient($account);

            // Construir mensaje MIME
            $message = $this->buildMimeMessage($emailData);

            // Enviar
            $gmailMessage = new Gmail\Message();
            $gmailMessage->setRaw($message);

            $sentMessage = $this->gmail->users_messages->send('me', $gmailMessage);

            // Guardar en nuestra BD
            $email = Email::create([
                'user_id' => $account->user_id,
                'email_account_id' => $account->id,
                'provider' => 'gmail',
                'provider_message_id' => $sentMessage->getId(),
                'provider_thread_id' => $sentMessage->getThreadId(),
                'subject' => $emailData['subject'],
                'from_email' => $account->email_address,
                'from_name' => $account->display_name,
                'to_email' => $emailData['to'],
                'body_html' => $emailData['body'] ?? null,
                'status' => 'sent',
                'received_at' => now(),
            ]);

            return $email;
        } catch (\Exception $e) {
            Log::error('Failed to send Gmail message', [
                'account_id' => $account->id,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Construir mensaje MIME para envío
     */
    protected function buildMimeMessage(array $data): string
    {
        $boundary = uniqid('boundary_');

        $mime = "From: {$data['from']}\r\n";
        $mime .= "To: {$data['to']}\r\n";
        $mime .= "Subject: {$data['subject']}\r\n";
        $mime .= "MIME-Version: 1.0\r\n";
        $mime .= "Content-Type: multipart/alternative; boundary=\"{$boundary}\"\r\n\r\n";

        if (!empty($data['body_text'])) {
            $mime .= "--{$boundary}\r\n";
            $mime .= "Content-Type: text/plain; charset=UTF-8\r\n\r\n";
            $mime .= $data['body_text'] . "\r\n\r\n";
        }

        if (!empty($data['body'])) {
            $mime .= "--{$boundary}\r\n";
            $mime .= "Content-Type: text/html; charset=UTF-8\r\n\r\n";
            $mime .= $data['body'] . "\r\n\r\n";
        }

        $mime .= "--{$boundary}--";

        return base64_encode($mime);
    }
}
