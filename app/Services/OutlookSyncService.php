<?php

namespace App\Services;

use App\Models\EmailAccount;
use App\Models\Email;
use App\Models\SyncLog;
use Microsoft\Graph\Graph;
use Microsoft\Graph\Model;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use GuzzleHttp\Client;

class OutlookSyncService
{
    protected Graph $graph;

    public function __construct()
    {
        $this->graph = new Graph();
    }

    /**
     * Inicializar cliente con credenciales de la cuenta
     */
    protected function initializeClient(EmailAccount $account): void
    {
        // Verificar si el token está expirado
        if ($account->isTokenExpired() && $account->decrypted_refresh_token) {
            $this->refreshAccessToken($account);
        }

        $this->graph->setAccessToken($account->decrypted_access_token);
    }

    /**
     * Refrescar access token usando refresh token
     */
    protected function refreshAccessToken(EmailAccount $account): void
    {
        $client = new Client();

        try {
            $response = $client->post('https://login.microsoftonline.com/' . config('services.microsoft.tenant') . '/oauth2/v2.0/token', [
                'form_params' => [
                    'client_id' => config('services.microsoft.client_id'),
                    'client_secret' => config('services.microsoft.client_secret'),
                    'refresh_token' => $account->decrypted_refresh_token,
                    'grant_type' => 'refresh_token',
                ],
            ]);

            $tokenData = json_decode($response->getBody()->getContents(), true);

            $account->update([
                'access_token' => $tokenData['access_token'],
                'refresh_token' => $tokenData['refresh_token'] ?? $account->decrypted_refresh_token,
                'token_expires_at' => now()->addSeconds($tokenData['expires_in'] ?? 3600),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to refresh Microsoft token', [
                'account_id' => $account->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Sincronizar emails de Outlook hacia Vera
     */
    public function syncInbound(EmailAccount $account, string $syncType = 'incremental'): SyncLog
    {
        $syncLog = SyncLog::create([
            'email_account_id' => $account->id,
            'sync_type' => $syncType,
            'direction' => 'inbound',
            'status' => 'running',
            'started_at' => now(),
        ]);

        try {
            $this->initializeClient($account);

            $filter = $this->buildFilter($account, $syncType);
            $skipToken = null;
            $emailsFetched = 0;
            $emailsCreated = 0;
            $emailsUpdated = 0;
            $emailsFailed = 0;

            do {
                // Construir query
                $query = '/me/messages?' . http_build_query([
                    '$top' => 100,
                    '$orderby' => 'receivedDateTime DESC',
                    '$filter' => $filter,
                ]);

                if ($skipToken) {
                    $query .= '&$skiptoken=' . urlencode($skipToken);
                }

                // Obtener mensajes
                $response = $this->graph->createRequest('GET', $query)
                    ->setReturnType(Model\Message::class)
                    ->execute();

                $messages = $response;

                if (empty($messages)) {
                    break;
                }

                foreach ($messages as $message) {
                    try {
                        $emailsFetched++;

                        // Procesar y guardar email
                        $result = $this->processOutlookMessage($message, $account);

                        if ($result['action'] === 'created') {
                            $emailsCreated++;
                        } elseif ($result['action'] === 'updated') {
                            $emailsUpdated++;
                        }
                    } catch (\Exception $e) {
                        $emailsFailed++;
                        Log::error('Error processing Outlook message', [
                            'account_id' => $account->id,
                            'message_id' => $message->getId(),
                            'error' => $e->getMessage(),
                        ]);
                    }
                }

                // Check for next page
                $skipToken = $response->getNextLink() ? $this->extractSkipToken($response->getNextLink()) : null;
            } while ($skipToken);

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

            Log::error('Outlook sync failed', [
                'account_id' => $account->id,
                'error' => $e->getMessage(),
            ]);
        }

        return $syncLog;
    }

    /**
     * Construir filtro de búsqueda según el tipo de sincronización
     */
    protected function buildFilter(EmailAccount $account, string $syncType): string
    {
        $filters = [];

        // Sincronización incremental: solo nuevos desde última sync
        if ($syncType === 'incremental' && $account->last_sync_at) {
            $afterDate = $account->last_sync_at->format('Y-m-d\TH:i:s\Z');
            $filters[] = "receivedDateTime ge {$afterDate}";
        }

        // Sincronización completa con rango de fechas
        if ($syncType === 'full' && $account->sync_from_date) {
            $fromDate = Carbon::parse($account->sync_from_date)->format('Y-m-d\TH:i:s\Z');
            $filters[] = "receivedDateTime ge {$fromDate}";
        }

        // Filtrar carpetas específicas si está configurado
        if ($account->sync_folders && is_array($account->sync_folders) && !in_array('all', $account->sync_folders)) {
            $folderFilters = array_map(function($folder) {
                return "parentFolderId eq '{$folder}'";
            }, $account->sync_folders);
            $filters[] = '(' . implode(' or ', $folderFilters) . ')';
        }

        return implode(' and ', $filters) ?: 'receivedDateTime ge 1970-01-01T00:00:00Z';
    }

    /**
     * Extraer skip token de la URL de next page
     */
    protected function extractSkipToken(string $nextLink): ?string
    {
        if (preg_match('/\$skiptoken=([^&]+)/', $nextLink, $matches)) {
            return urldecode($matches[1]);
        }
        return null;
    }

    /**
     * Procesar mensaje de Outlook y guardar en BD
     */
    protected function processOutlookMessage($outlookMessage, EmailAccount $account): array
    {
        $messageId = $outlookMessage->getId();
        $conversationId = $outlookMessage->getConversationId();

        // Buscar si ya existe
        $email = Email::where('provider_message_id', $messageId)->first();

        $emailData = [
            'user_id' => $account->user_id,
            'email_account_id' => $account->id,
            'provider' => 'outlook',
            'provider_message_id' => $messageId,
            'provider_thread_id' => $conversationId,
            'subject' => $outlookMessage->getSubject() ?? '(Sin asunto)',
            'from_email' => $outlookMessage->getFrom() ? $outlookMessage->getFrom()->getEmailAddress()->getAddress() : null,
            'from_name' => $outlookMessage->getFrom() ? $outlookMessage->getFrom()->getEmailAddress()->getName() : null,
            'to_email' => $this->extractFirstRecipientEmail($outlookMessage->getToRecipients()),
            'to_name' => $this->extractFirstRecipientName($outlookMessage->getToRecipients()),
            'cc' => $this->formatRecipients($outlookMessage->getCcRecipients()),
            'bcc' => $this->formatRecipients($outlookMessage->getBccRecipients()),
            'body_text' => $outlookMessage->getBody() && $outlookMessage->getBody()->getContentType() === 'text'
                ? $outlookMessage->getBody()->getContent()
                : null,
            'body_html' => $outlookMessage->getBody() && $outlookMessage->getBody()->getContentType() === 'html'
                ? $outlookMessage->getBody()->getContent()
                : null,
            'received_at' => $this->parseOutlookDate($outlookMessage->getReceivedDateTime()),
            'is_read' => $outlookMessage->getIsRead() ?? false,
            'is_starred' => $outlookMessage->getFlag() ? $outlookMessage->getFlag()->getFlagStatus() === 'flagged' : false,
            'category' => $this->determineCategory($outlookMessage),
            'status' => 'received',
            'metadata' => [
                'importance' => $outlookMessage->getImportance(),
                'has_attachments' => $outlookMessage->getHasAttachments(),
                'internet_message_id' => $outlookMessage->getInternetMessageId(),
                'categories' => $outlookMessage->getCategories(),
            ],
        ];

        if ($email) {
            $email->update($emailData);
            return ['action' => 'updated', 'email' => $email];
        } else {
            $email = Email::create($emailData);
            return ['action' => 'created', 'email' => $email];
        }
    }

    /**
     * Extraer primer email de destinatarios
     */
    protected function extractFirstRecipientEmail(?array $recipients): ?string
    {
        if (empty($recipients)) return null;
        return $recipients[0]->getEmailAddress()->getAddress() ?? null;
    }

    /**
     * Extraer primer nombre de destinatarios
     */
    protected function extractFirstRecipientName(?array $recipients): ?string
    {
        if (empty($recipients)) return null;
        return $recipients[0]->getEmailAddress()->getName() ?? null;
    }

    /**
     * Formatear lista de destinatarios a string
     */
    protected function formatRecipients(?array $recipients): ?string
    {
        if (empty($recipients)) return null;

        $formatted = array_map(function($recipient) {
            $email = $recipient->getEmailAddress();
            $name = $email->getName();
            $address = $email->getAddress();
            return $name ? "{$name} <{$address}>" : $address;
        }, $recipients);

        return implode(', ', $formatted);
    }

    /**
     * Parsear fecha de Outlook
     */
    protected function parseOutlookDate(?string $dateString): Carbon
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
     * Determinar categoría basada en propiedades del mensaje
     */
    protected function determineCategory($message): string
    {
        // Outlook no tiene categorías automáticas como Gmail
        // Usar la carpeta padre o categorías personalizadas
        $categories = $message->getCategories();

        if ($categories && in_array('Social', $categories)) return 'social';
        if ($categories && in_array('Promotions', $categories)) return 'promotions';

        // Por defecto, todo va a primary
        return 'primary';
    }

    /**
     * Marcar email como leído en Outlook
     */
    public function markAsRead(EmailAccount $account, Email $email): bool
    {
        try {
            $this->initializeClient($account);

            $this->graph->createRequest('PATCH', "/me/messages/{$email->provider_message_id}")
                ->attachBody(['isRead' => true])
                ->execute();

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to mark Outlook message as read', [
                'email_id' => $email->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Archivar email en Outlook (mover a archivo)
     */
    public function archive(EmailAccount $account, Email $email): bool
    {
        try {
            $this->initializeClient($account);

            // Mover a carpeta de archivo
            $this->graph->createRequest('POST', "/me/messages/{$email->provider_message_id}/move")
                ->attachBody(['destinationId' => 'archive'])
                ->execute();

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to archive Outlook message', [
                'email_id' => $email->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Enviar email desde Outlook
     */
    public function sendEmail(EmailAccount $account, array $emailData): ?Email
    {
        try {
            $this->initializeClient($account);

            // Construir mensaje
            $message = [
                'subject' => $emailData['subject'],
                'body' => [
                    'contentType' => 'HTML',
                    'content' => $emailData['body'] ?? '',
                ],
                'toRecipients' => [
                    [
                        'emailAddress' => [
                            'address' => $emailData['to'],
                        ],
                    ],
                ],
            ];

            // Enviar
            $sentMessage = $this->graph->createRequest('POST', '/me/sendMail')
                ->attachBody(['message' => $message])
                ->execute();

            // Guardar en nuestra BD
            $email = Email::create([
                'user_id' => $account->user_id,
                'email_account_id' => $account->id,
                'provider' => 'outlook',
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
            Log::error('Failed to send Outlook message', [
                'account_id' => $account->id,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }
}
