<?php

namespace App\Traits;

use App\Models\VeraConfig;
use Google\Client as GoogleClient;
use Illuminate\Support\Facades\Log;

trait GoogleOAuthTrait
{
    private function refreshGoogleToken(GoogleClient $client, VeraConfig $config): void
    {
        // La librería de Google interpreta expires_in + created para saber si el token caducó.
        // Pasamos created=now() y expires_in=segundos restantes para que el cálculo sea correcto.
        $secondsRemaining = $config->google_token_expires_at
            ? max(0, $config->google_token_expires_at->getTimestamp() - time())
            : 0;

        $client->setAccessToken([
            'access_token'  => $config->google_access_token,
            'refresh_token' => $config->google_refresh_token,
            'token_type'    => 'Bearer',
            'created'       => time(),
            'expires_in'    => $secondsRemaining,
        ]);

        if (!$client->isAccessTokenExpired()) {
            return;
        }

        if (!$config->google_refresh_token) {
            throw new \RuntimeException('Google OAuth: no hay refresh_token. El usuario debe reconectar su cuenta.');
        }

        $newToken = $client->fetchAccessTokenWithRefreshToken($config->google_refresh_token);

        if (isset($newToken['error'])) {
            throw new \RuntimeException(
                'Google OAuth: error al refrescar token: ' . ($newToken['error_description'] ?? $newToken['error'])
            );
        }

        $config->update([
            'google_access_token'     => $newToken['access_token'],
            'google_token_expires_at' => now()->addSeconds($newToken['expires_in'] ?? 3600),
        ]);

        Log::info('🔄 Google token refrescado', ['user_id' => $config->user_id]);
    }
}
