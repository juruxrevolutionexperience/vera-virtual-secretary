<?php

namespace App\Services\Security;

use App\Models\User;
use App\Models\PasswordPolicy;
use App\Models\PasswordHistory;
use App\Models\SecurityEvent;
use Illuminate\Support\Facades\Hash;

class PasswordPolicyService
{
    /**
     * Validar contraseña contra la política activa
     */
    public function validatePassword(string $password, User $user = null): array
    {
        $policy = PasswordPolicy::getActive();

        if (!$policy) {
            return [];
        }

        $errors = $policy->validate($password);

        // Verificar si la contraseña fue usada antes
        if ($user && $policy->prevent_reuse) {
            if (PasswordHistory::wasUsedBefore($user->id, $password, $policy->history_count)) {
                $errors[] = "No puedes reutilizar una de tus últimas {$policy->history_count} contraseñas.";
            }
        }

        return $errors;
    }

    /**
     * Cambiar contraseña de usuario
     */
    public function changePassword(User $user, string $newPassword, string $changedBy = 'user'): bool
    {
        // Validar nueva contraseña
        $errors = $this->validatePassword($newPassword, $user);
        if (!empty($errors)) {
            throw new \Exception(implode(' ', $errors));
        }

        // Guardar en historial
        PasswordHistory::record(
            $user->id,
            Hash::make($newPassword),
            request()->ip(),
            request()->userAgent(),
            $changedBy
        );

        // Actualizar contraseña
        $user->password = Hash::make($newPassword);
        $user->password_changed_at = now();
        $user->save();

        SecurityEvent::log('password_changed', $user->id, 'info', [
            'changed_by' => $changedBy,
        ]);

        return true;
    }

    /**
     * Verificar si la contraseña expiró
     */
    public function isPasswordExpired(User $user): bool
    {
        $policy = PasswordPolicy::getActive();

        if (!$policy || !$policy->expires) {
            return false;
        }

        if (!$user->password_changed_at) {
            return false;
        }

        $expiryDate = $user->password_changed_at->addDays($policy->expiry_days);

        return now()->greaterThan($expiryDate);
    }

    /**
     * Días hasta que expire la contraseña
     */
    public function daysUntilExpiry(User $user): ?int
    {
        $policy = PasswordPolicy::getActive();

        if (!$policy || !$policy->expires || !$user->password_changed_at) {
            return null;
        }

        $expiryDate = $user->password_changed_at->addDays($policy->expiry_days);
        $daysLeft = now()->diffInDays($expiryDate, false);

        return $daysLeft > 0 ? (int)$daysLeft : 0;
    }

    /**
     * Verificar si debe mostrar aviso de expiración
     */
    public function shouldWarnAboutExpiry(User $user): bool
    {
        $daysLeft = $this->daysUntilExpiry($user);

        if ($daysLeft === null) {
            return false;
        }

        $policy = PasswordPolicy::getActive();

        return $daysLeft <= $policy->expiry_warning_days;
    }

    /**
     * Obtener política activa
     */
    public function getActivePolicy(): ?PasswordPolicy
    {
        return PasswordPolicy::getActive();
    }

    /**
     * Obtener descripción de requisitos
     */
    public function getRequirementsDescription(): string
    {
        $policy = $this->getActivePolicy();

        return $policy ? $policy->getRequirementsDescription() : 'No hay política activa.';
    }
}
