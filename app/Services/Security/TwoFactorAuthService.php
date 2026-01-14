<?php

namespace App\Services\Security;

use App\Models\User;
use App\Models\SecurityEvent;
use Illuminate\Support\Collection;
use PragmaRX\Google2FA\Google2FA;

class TwoFactorAuthService
{
    protected Google2FA $google2fa;

    public function __construct()
    {
        $this->google2fa = new Google2FA();
    }

    /**
     * Generar secret key para 2FA
     */
    public function generateSecretKey(): string
    {
        return $this->google2fa->generateSecretKey();
    }

    /**
     * Habilitar 2FA para un usuario
     */
    public function enable(User $user, string $code): bool
    {
        if (!$user->two_factor_secret) {
            throw new \Exception('Primero debes generar un código QR');
        }

        // Verificar el código
        if (!$this->verify($user, $code)) {
            return false;
        }

        // Generar códigos de recuperación
        $recoveryCodes = $this->generateRecoveryCodes();

        $user->update([
            'two_factor_enabled' => true,
            'two_factor_enabled_at' => now(),
            'two_factor_recovery_codes' => encrypt(json_encode($recoveryCodes)),
        ]);

        SecurityEvent::log('2fa_enabled', $user->id, 'info');

        return true;
    }

    /**
     * Deshabilitar 2FA
     */
    public function disable(User $user): void
    {
        $user->update([
            'two_factor_enabled' => false,
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_enabled_at' => null,
        ]);

        SecurityEvent::log('2fa_disabled', $user->id, 'warning');
    }

    /**
     * Verificar código 2FA
     */
    public function verify(User $user, string $code): bool
    {
        if (!$user->two_factor_secret) {
            return false;
        }

        $valid = $this->google2fa->verifyKey(
            decrypt($user->two_factor_secret),
            $code,
            2 // window tolerance
        );

        if (!$valid) {
            SecurityEvent::log('2fa_verification_failed', $user->id, 'warning', [
                'code_attempt' => substr($code, 0, 2) . '****',
            ]);
        }

        return $valid;
    }

    /**
     * Verificar código de recuperación
     */
    public function verifyRecoveryCode(User $user, string $code): bool
    {
        if (!$user->two_factor_recovery_codes) {
            return false;
        }

        $recoveryCodes = json_decode(decrypt($user->two_factor_recovery_codes), true);

        if (!in_array($code, $recoveryCodes)) {
            return false;
        }

        // Remover el código usado
        $recoveryCodes = array_values(array_diff($recoveryCodes, [$code]));
        $user->update([
            'two_factor_recovery_codes' => encrypt(json_encode($recoveryCodes)),
        ]);

        SecurityEvent::log('2fa_recovery_code_used', $user->id, 'warning', [
            'remaining_codes' => count($recoveryCodes),
        ]);

        return true;
    }

    /**
     * Generar URL para QR code
     */
    public function getQRCodeUrl(User $user): string
    {
        if (!$user->two_factor_secret) {
            $secret = $this->generateSecretKey();
            $user->update(['two_factor_secret' => encrypt($secret)]);
        }

        return $this->google2fa->getQRCodeUrl(
            config('app.name'),
            $user->email,
            decrypt($user->two_factor_secret)
        );
    }

    /**
     * Generar códigos de recuperación
     */
    public function generateRecoveryCodes(int $count = 8): array
    {
        $codes = [];
        for ($i = 0; $i < $count; $i++) {
            $codes[] = strtoupper(substr(bin2hex(random_bytes(5)), 0, 10));
        }
        return $codes;
    }

    /**
     * Regenerar códigos de recuperación
     */
    public function regenerateRecoveryCodes(User $user): array
    {
        $codes = $this->generateRecoveryCodes();
        $user->update([
            'two_factor_recovery_codes' => encrypt(json_encode($codes)),
        ]);

        SecurityEvent::log('2fa_recovery_codes_regenerated', $user->id, 'info');

        return $codes;
    }

    /**
     * Obtener códigos de recuperación restantes
     */
    public function getRecoveryCodes(User $user): ?array
    {
        if (!$user->two_factor_recovery_codes) {
            return null;
        }

        return json_decode(decrypt($user->two_factor_recovery_codes), true);
    }
}
