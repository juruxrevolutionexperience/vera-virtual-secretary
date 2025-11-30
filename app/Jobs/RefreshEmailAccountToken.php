<?php

namespace App\Jobs;

use App\Models\EmailAccount;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use GuzzleHttp\Client;

class RefreshEmailAccountToken implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Número de intentos antes de fallar
     */
    public $tries = 3;

    /**
     * Tiempo máximo de ejecución
     */
    public $timeout = 60;

    /**
     * La cuenta de email
     */
    protected EmailAccount $emailAccount;

    /**
     * Create a new job instance.
     */
    public function __construct(EmailAccount $emailAccount)
    {
        $this->emailAccount = $emailAccount;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Verificar que el token está expirado o próximo a expirar
        if (!$this->emailAccount->isTokenExpired() &&
            $this->emailAccount->token_expires_at->diffInMinutes(now()) > 10) {
            Log::info('Token still valid, skipping refresh', [
                'account_id' => $this->emailAccount->id,
            ]);
            return;
        }

        Log::info('Refreshing token', [
            'account_id' => $this->emailAccount->id,
            'provider' => $this->emailAccount->provider,
        ]);

        try {
            if ($this->emailAccount->provider === 'gmail') {
                $this->refreshGoogleToken();
            } elseif ($this->emailAccount->provider === 'outlook') {
                $this->refreshMicrosoftToken();
            }

            Log::info('Token refreshed successfully', [
                'account_id' => $this->emailAccount->id,
            ]);

        } catch (\Exception $e) {
            Log::error('Token refresh failed', [
                'account_id' => $this->emailAccount->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Refrescar token de Google
     */
    protected function refreshGoogleToken(): void
    {
        $client = new Client();

        $response = $client->post('https://oauth2.googleapis.com/token', [
            'form_params' => [
                'client_id' => config('services.google.client_id'),
                'client_secret' => config('services.google.client_secret'),
                'refresh_token' => $this->emailAccount->decrypted_refresh_token,
                'grant_type' => 'refresh_token',
            ],
        ]);

        $tokenData = json_decode($response->getBody()->getContents(), true);

        $this->emailAccount->update([
            'access_token' => $tokenData['access_token'],
            'token_expires_at' => now()->addSeconds($tokenData['expires_in'] ?? 3600),
        ]);
    }

    /**
     * Refrescar token de Microsoft
     */
    protected function refreshMicrosoftToken(): void
    {
        $client = new Client();

        $response = $client->post('https://login.microsoftonline.com/' . config('services.microsoft.tenant') . '/oauth2/v2.0/token', [
            'form_params' => [
                'client_id' => config('services.microsoft.client_id'),
                'client_secret' => config('services.microsoft.client_secret'),
                'refresh_token' => $this->emailAccount->decrypted_refresh_token,
                'grant_type' => 'refresh_token',
            ],
        ]);

        $tokenData = json_decode($response->getBody()->getContents(), true);

        $this->emailAccount->update([
            'access_token' => $tokenData['access_token'],
            'refresh_token' => $tokenData['refresh_token'] ?? $this->emailAccount->decrypted_refresh_token,
            'token_expires_at' => now()->addSeconds($tokenData['expires_in'] ?? 3600),
        ]);
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Token refresh permanently failed', [
            'account_id' => $this->emailAccount->id,
            'error' => $exception->getMessage(),
        ]);

        // Marcar cuenta como error
        $this->emailAccount->update([
            'sync_status' => 'error',
        ]);
    }
}
