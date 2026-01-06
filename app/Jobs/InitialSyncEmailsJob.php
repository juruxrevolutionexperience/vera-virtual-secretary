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

class InitialSyncEmailsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 1;
    public $timeout = 1800; // 30 minutos

    protected EmailAccount $emailAccount;

    public function __construct(EmailAccount $emailAccount)
    {
        $this->emailAccount = $emailAccount;
    }

    public function handle(): void
    {
        // Verificar que la cuenta todavía existe en la base de datos
        $accountExists = EmailAccount::where('id', $this->emailAccount->id)->exists();

        if (!$accountExists) {
            Log::warning('⚠️ Email account no longer exists, cancelling sync', [
                'account_id' => $this->emailAccount->id,
                'email' => $this->emailAccount->email_address,
            ]);
            return; // Salir silenciosamente
        }

        Log::info('🚀 Starting INITIAL sync', [
            'account_id' => $this->emailAccount->id,
            'email' => $this->emailAccount->email_address,
        ]);

        // Marcar como sincronizando
        $this->emailAccount->update([
            'sync_status' => 'initial_sync_running',
            'synced_emails_count' => 0,
        ]);

        try {
            if ($this->emailAccount->provider === 'gmail') {
                $service = new GmailSyncService();
                $service->initialSync($this->emailAccount);
            } elseif ($this->emailAccount->provider === 'outlook') {
                $service = new OutlookSyncService();
                $service->initialSync($this->emailAccount);
            }

            // Marcar sincronización inicial como completada
            $this->emailAccount->update([
                'sync_status' => 'idle',
                'initial_sync_completed_at' => now(),
                'last_sync_at' => now(),
            ]);

            Log::info('✅ Initial sync completed successfully', [
                'account_id' => $this->emailAccount->id,
                'total_synced' => $this->emailAccount->synced_emails_count,
            ]);

            // Disparar análisis automático de Vera
            Log::info('🧠 Dispatching Vera analysis for user', [
                'user_id' => $this->emailAccount->user_id,
            ]);
            \App\Jobs\AnalyzeUserEmailsJob::dispatch($this->emailAccount->user_id);

        } catch (\Exception $e) {
            Log::error('❌ Initial sync failed', [
                'account_id' => $this->emailAccount->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $this->emailAccount->update([
                'sync_status' => 'error',
                'sync_error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        $this->emailAccount->update([
            'sync_status' => 'error',
            'sync_error' => 'Initial sync failed: ' . $exception->getMessage(),
        ]);
    }
}