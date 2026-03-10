<?php

namespace App\Console\Commands;

use App\Models\EmailAccount;
use App\Services\GmailSyncService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class RenewGmailWatches extends Command
{
    protected $signature = 'gmail:renew-watches
                            {--force : Renovar todos los watches aunque no hayan expirado}';

    protected $description = 'Renueva los Gmail watches antes de que expiren (duran 7 días)';

    public function handle(GmailSyncService $gmailService)
    {
        // Solo ejecutar si hay topic configurado
        if (!config('vera.gmail_pubsub_topic')) {
            $this->info('📭 GMAIL_PUBSUB_TOPIC no configurado, saltando renovación.');
            return Command::SUCCESS;
        }

        $this->info('🔄 Renovando Gmail watches...');

        // Cuentas cuyo watch expira en las próximas 24 horas (o ya expiró)
        $query = EmailAccount::where('provider', 'gmail')
            ->where('sync_enabled', true)
            ->where('sync_status', 'active');

        if (!$this->option('force')) {
            $query->where(function ($q) {
                $q->whereNull('watch_expires_at')
                  ->orWhere('watch_expires_at', '<=', now()->addHours(24));
            });
        }

        $accounts = $query->get();

        if ($accounts->isEmpty()) {
            $this->info('✅ No hay watches que renovar.');
            return Command::SUCCESS;
        }

        $this->info("📋 Cuentas a renovar: {$accounts->count()}");

        $ok    = 0;
        $fails = 0;

        foreach ($accounts as $account) {
            $this->line("   🔄 {$account->email_address}");

            $success = $gmailService->setupWatch($account);

            if ($success) {
                $this->info("   ✅ Watch renovado");
                $ok++;
            } else {
                $this->warn("   ⚠️  No se pudo renovar (normal en desarrollo)");
                $fails++;
            }
        }

        $this->newLine();
        $this->info("Resultado: {$ok} renovados, {$fails} fallidos");

        return Command::SUCCESS;
    }
}
