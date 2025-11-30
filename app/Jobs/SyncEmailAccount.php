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

class SyncEmailAccount implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Número de intentos antes de fallar
     */
    public $tries = 3;

    /**
     * Tiempo máximo de ejecución (10 minutos)
     */
    public $timeout = 600;

    /**
     * La cuenta de email a sincronizar
     */
    protected EmailAccount $emailAccount;

    /**
     * Tipo de sincronización
     */
    protected string $syncType;

    /**
     * Create a new job instance.
     */
    public function __construct(EmailAccount $emailAccount, string $syncType = 'incremental')
    {
        $this->emailAccount = $emailAccount;
        $this->syncType = $syncType;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Verificar que la sincronización está habilitada
        if (!$this->emailAccount->sync_enabled) {
            Log::info('Sync disabled for account', ['account_id' => $this->emailAccount->id]);
            return;
        }

        // Verificar que la cuenta no está en pausa
        if ($this->emailAccount->sync_status === 'paused') {
            Log::info('Sync paused for account', ['account_id' => $this->emailAccount->id]);
            return;
        }

        Log::info('Starting email sync', [
            'account_id' => $this->emailAccount->id,
            'provider' => $this->emailAccount->provider,
            'sync_type' => $this->syncType,
        ]);

        try {
            // Sincronizar según el proveedor
            if ($this->emailAccount->provider === 'gmail') {
                $service = new GmailSyncService();
                $syncLog = $service->syncInbound($this->emailAccount, $this->syncType);
            } elseif ($this->emailAccount->provider === 'outlook') {
                $service = new OutlookSyncService();
                $syncLog = $service->syncInbound($this->emailAccount, $this->syncType);
            } else {
                Log::error('Unknown provider', ['provider' => $this->emailAccount->provider]);
                return;
            }

            Log::info('Email sync completed', [
                'account_id' => $this->emailAccount->id,
                'sync_log_id' => $syncLog->id,
                'status' => $syncLog->status,
                'emails_created' => $syncLog->emails_created,
                'emails_updated' => $syncLog->emails_updated,
            ]);

        } catch (\Exception $e) {
            Log::error('Email sync job failed', [
                'account_id' => $this->emailAccount->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Marcar la cuenta con error
            $this->emailAccount->update(['sync_status' => 'error']);

            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Email sync job permanently failed', [
            'account_id' => $this->emailAccount->id,
            'error' => $exception->getMessage(),
        ]);

        // Marcar cuenta como error y deshabilitar sincronización automática
        $this->emailAccount->update([
            'sync_status' => 'error',
            'sync_enabled' => false,
        ]);
    }
}
