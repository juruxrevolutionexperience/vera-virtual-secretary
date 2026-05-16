<?php

namespace App\Console\Commands;

use App\Jobs\ProcessIncomingMessage;
use App\Models\User;
use App\Models\VeraActivityLog;
use App\Models\VeraConfig;
use App\Services\GmailService;
use Illuminate\Console\Command;

/**
 * vera:process-emails
 *
 * Lee emails no leídos del Gmail del usuario y los procesa con Vera.
 * Para no depender del scope gmail.modify (restricted, requiere CASA),
 * llevamos un registro local en gmail_processed_messages — los emails procesados
 * quedan como NO leídos en Gmail; el filtrado se hace contra la tabla local.
 *
 * Uso:
 *   ./vendor/bin/sail artisan vera:process-emails          (todos los usuarios con Gmail)
 *   ./vendor/bin/sail artisan vera:process-emails --user=4  (usuario concreto)
 *   ./vendor/bin/sail artisan vera:process-emails --dry-run  (ver emails sin procesar)
 */
class ProcessGmailEmails extends Command
{
    protected $signature   = 'vera:process-emails
                                {--user= : ID del usuario concreto}
                                {--dry-run : Mostrar emails encontrados sin procesarlos}
                                {--max=5 : Máximo de emails a procesar por usuario}';

    protected $description = 'Lee emails no leídos de Gmail y los procesa con Vera';

    public function handle(GmailService $gmail): int
    {
        $userId  = $this->option('user');
        $dryRun  = $this->option('dry-run');
        $max     = (int) $this->option('max');

        // Obtener usuarios a procesar
        $query = VeraConfig::query()
            ->whereNotNull('google_access_token')
            ->whereNotNull('google_refresh_token')
            ->where('onboarding_completed', true);

        if ($userId) {
            $query->where('user_id', $userId);
        }

        $configs = $query->get();

        if ($configs->isEmpty()) {
            $this->info('Sin usuarios con Gmail conectado — nada que procesar.');
            return self::SUCCESS;
        }

        $this->info("Procesando {$configs->count()} usuario(s)...");
        $this->newLine();

        foreach ($configs as $config) {
            $user = User::find($config->user_id);
            if (!$user) continue;

            $this->info("👤 Usuario: {$user->email} (ID: {$user->id})");

            try {
                $emails = $gmail->getUnreadEmails($config, $max);
            } catch (\Exception $e) {
                $this->error("  ❌ Error leyendo Gmail: {$e->getMessage()}");
                continue;
            }

            if (empty($emails)) {
                $this->line("  📭 No hay emails no leídos.");
                $this->newLine();
                continue;
            }

            $count = count($emails);
            $this->line("  📬 {$count} email(s) encontrado(s):");

            foreach ($emails as $email) {
                $this->line("  ─────────────────────────────────────");
                $this->line("  De:       {$email['from']}");
                $this->line("  Asunto:   {$email['subject']}");
                $this->line("  Snippet:  {$email['snippet']}");

                // ── Filtro spam / newsletters ──────────────────────────────────
                if ($this->isSpam($email)) {
                    $this->warn("  [SPAM] Ignorado — marcando como procesado en tabla local.");
                    $gmail->markProcessed($config, $email['id']);
                    VeraActivityLog::record([
                        'user_id'       => $user->id,
                        'activity_type' => 'message_received',
                        'title'         => 'Email spam ignorado — ' . $this->extractName($email['from']) ?? $email['from'],
                        'description'   => $email['snippet'],
                        'metadata'      => [
                            'channel'  => 'email',
                            'contact'  => $email['from'],
                            'subject'  => $email['subject'],
                            'ignored'  => true,
                            'reason'   => 'spam',
                        ],
                        'counts_towards_limit' => false,
                        'status'        => 'failed',
                    ]);
                    continue;
                }

                if ($dryRun) {
                    $this->warn("  [DRY-RUN] No se procesa.");
                    continue;
                }

                // Lanzar Job
                ProcessIncomingMessage::dispatch(
                    userId:            $user->id,
                    channel:           'email',
                    message:           $email['subject'] . "\n\n" . $email['body'],
                    contactIdentifier: $email['from'],
                    contactName:       $this->extractName($email['from']),
                    metadata:          [
                        'gmail_message_id' => $email['id'],
                        'thread_id'        => $email['thread_id'],
                        'subject'          => $email['subject'],
                        'from'             => $email['from'],
                    ],
                );

                // Registrar como procesado en tabla local (sin tocar Gmail)
                $gmail->markProcessed($config, $email['id']);
                $this->info("  ✅ Procesado y registrado como completado.");
            }

            $this->newLine();
        }

        $this->info('✅ Hecho.');
        return self::SUCCESS;
    }

    private function extractName(string $from): ?string
    {
        // "Nombre Apellido <email@ejemplo.com>" → "Nombre Apellido"
        if (preg_match('/^([^<]+)</', $from, $matches)) {
            return trim($matches[1]);
        }
        return null;
    }

    /**
     * Detecta si un email es spam, newsletter o automatizado.
     * Reglas: cabecera List-Unsubscribe, patrones no-reply en From,
     * y dominios conocidos de marketing/notificaciones.
     */
    private function isSpam(array $email): bool
    {
        // Cabecera List-Unsubscribe → definitivamente un boletín
        if ($email['has_list_unsubscribe'] ?? false) {
            return true;
        }

        $from = strtolower($email['from']);

        // Patrones no-reply en la dirección
        $noReplyPatterns = [
            'no-reply', 'noreply', 'do-not-reply', 'donotreply',
            'notifications@', 'alerts@', 'news@', 'newsletter@',
            'updates@', 'mailer@', 'bounce@', 'info@',
        ];

        foreach ($noReplyPatterns as $pattern) {
            if (str_contains($from, $pattern)) {
                return true;
            }
        }

        // Dominios conocidos de marketing / notificaciones automáticas
        $spamDomains = [
            'linkedin.com', 'heygen.com', 'mailchimp.com', 'sendgrid.net',
            'constantcontact.com', 'campaign-archive', 'hubspot.com',
            'salesforce.com', 'marketo.com', 'klaviyo.com',
            'amazonses.com', 'sendpulse.com', 'getresponse.com',
        ];

        foreach ($spamDomains as $domain) {
            if (str_contains($from, $domain)) {
                return true;
            }
        }

        // Notificaciones automáticas de Google Calendar — aceptaciones, rechazos, cancelaciones
        // El email viene del asistente real (no de google.com) pero el asunto tiene un prefijo fijo
        $calendarPrefixes = [
            'Aceptado:', 'Rechazado:', 'Tentativo:', 'Cancelado:', 'Actualizado:',
            'Accepted:', 'Declined:', 'Tentative:', 'Cancelled:', 'Updated:',
        ];
        $subject = $email['subject'] ?? '';
        foreach ($calendarPrefixes as $prefix) {
            if (str_starts_with($subject, $prefix)) {
                return true;
            }
        }

        return false;
    }
}
