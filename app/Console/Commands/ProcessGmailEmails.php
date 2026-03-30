<?php

namespace App\Console\Commands;

use App\Jobs\ProcessIncomingMessage;
use App\Models\User;
use App\Models\VeraConfig;
use App\Services\GmailService;
use Illuminate\Console\Command;

/**
 * vera:process-emails
 *
 * Comando de prueba para disparar el flujo completo email → Vera:
 *   1. Lee emails no leídos del Gmail del usuario
 *   2. Para cada email, lanza el Job ProcessIncomingMessage (canal: email)
 *   3. Marca el email como leído
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
            $this->warn('No hay usuarios con Gmail conectado y onboarding completado.');
            return self::FAILURE;
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
                    metadata:          ['gmail_message_id' => $email['id'], 'subject' => $email['subject']],
                );

                // Marcar como leído
                try {
                    $gmail->markAsRead($config, $email['id']);
                    $this->info("  ✅ Procesado y marcado como leído.");
                } catch (\Exception $e) {
                    $this->warn("  ⚠️  No se pudo marcar como leído: {$e->getMessage()}");
                }
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
}
