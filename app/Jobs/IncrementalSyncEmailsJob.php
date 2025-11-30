<?php

namespace App\Jobs;

use App\Models\EmailAccount;
use App\Services\GmailSyncService;
use App\Services\OutlookSyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class IncrementalSyncEmailsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 300; // 5 minutos

    protected EmailAccount $emailAccount;

    public function __construct(EmailAccount $emailAccount)
    {
        $this->emailAccount = $emailAccount;
    }

    public function handle(): void
    {
        Log::info('🔄 Starting INCREMENTAL sync', [
            'account_id' => $this->emailAccount->id,
            'email' => $this->emailAccount->email_address,
        ]);

        // Verificar que la sincronización inicial esté completada
        if (!$this->emailAccount->hasCompletedInitialSync()) {
            Log::warning('⚠️ Incremental sync skipped: initial sync not completed', [
                'account_id' => $this->emailAccount->id,
            ]);
            return;
        }

        // Verificar que la cuenta esté activa
        if (!$this->emailAccount->sync_enabled) {
            Log::info('⏸️ Incremental sync skipped: sync disabled', [
                'account_id' => $this->emailAccount->id,
            ]);
            return;
        }

        try {
            // NO cambiar sync_status - esto debe ser silencioso
            // Solo actualizar last_sync_at

            if ($this->emailAccount->provider === 'gmail') {
                $service = new GmailSyncService();
                $service->incrementalSync($this->emailAccount);
            } elseif ($this->emailAccount->provider === 'outlook') {
                $service = new OutlookSyncService();
                $service->incrementalSync($this->emailAccount);
            }

            // Actualizar timestamp de última sincronización
            $this->emailAccount->update([
                'last_sync_at' => now(),
            ]);

            Log::info('✅ Incremental sync completed successfully', [
                'account_id' => $this->emailAccount->id,
                'total_synced' => $this->emailAccount->synced_emails_count,
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Incremental sync failed', [
                'account_id' => $this->emailAccount->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // No marcar como error - solo loggear
            // La próxima ejecución lo intentará de nuevo
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('❌ Incremental sync job failed after all retries', [
            'account_id' => $this->emailAccount->id,
            'error' => $exception->getMessage(),
        ]);

        // No actualizar sync_status a error
        // Solo loggear para debugging
    }
}
