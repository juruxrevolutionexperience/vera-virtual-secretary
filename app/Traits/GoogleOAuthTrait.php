<?php

namespace App\Traits;

use App\Models\VeraConfig;
use Google\Client as GoogleClient;
use Illuminate\Support\Facades\Log;

trait GoogleOAuthTrait
{
    private function refreshGoogleToken(GoogleClient $client, VeraConfig $config): void
    {
        $client->setAccessToken([
            'access_token'  => $config->google_access_token,
            'refresh_token' => $config->google_refresh_token,
            'expires_in'    => $config->google_token_expires_at
                ? now()->diffInSeconds($config->google_token_expires_at, false)
                : 0,
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
