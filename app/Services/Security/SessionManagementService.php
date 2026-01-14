<?php

namespace App\Services\Security;

use App\Models\User;
use App\Models\UserSession;
use App\Models\SecurityEvent;
use Illuminate\Support\Collection;

class SessionManagementService
{
    /**
     * Obtener sesiones activas de un usuario
     */
    public function getActiveSessions(User $user): Collection
    {
        return UserSession::where('user_id', $user->id)
            ->active()
            ->orderBy('last_activity', 'desc')
            ->get();
    }

    /**
     * Obtener sesión actual
     */
    public function getCurrentSession(User $user): ?UserSession
    {
        return UserSession::where('user_id', $user->id)
            ->where('session_id', session()->getId())
            ->first();
    }

    /**
     * Revocar una sesión específica
     */
    public function revokeSession(UserSession $session, string $reason = 'manual'): void
    {
        $session->revoke($reason);

        SecurityEvent::log('session_revoked', $session->user_id, 'info', [
            'session_id' => $session->session_id,
            'device' => $session->device_type,
            'reason' => $reason,
        ]);
    }

    /**
     * Revocar todas las sesiones excepto la actual
     */
    public function revokeAllExceptCurrent(User $user): int
    {
        $currentSessionId = session()->getId();
        $count = UserSession::revokeAllExceptCurrent($user->id, $currentSessionId);

        SecurityEvent::log('all_sessions_revoked', $user->id, 'warning', [
            'revoked_count' => $count,
        ]);

        return $count;
    }

    /**
     * Marcar dispositivo como confiable
     */
    public function trustDevice(UserSession $session): void
    {
        $session->update(['is_trusted' => true]);

        SecurityEvent::log('device_trusted', $session->user_id, 'info', [
            'device' => $session->device_type,
            'platform' => $session->platform,
        ]);
    }

    /**
     * Remover confianza de un dispositivo
     */
    public function untrustDevice(UserSession $session): void
    {
        $session->update(['is_trusted' => false]);

        SecurityEvent::log('device_untrusted', $session->user_id, 'info', [
            'device' => $session->device_type,
        ]);
    }

    /**
     * Detectar actividad sospechosa
     */
    public function detectSuspiciousActivity(User $user): array
    {
        $suspicious = UserSession::detectSuspiciousActivity($user->id);

        if (!empty($suspicious)) {
            foreach ($suspicious as $alert) {
                SecurityEvent::log('suspicious_activity_detected', $user->id, 'danger', $alert);
            }
        }

        return $suspicious;
    }

    /**
     * Limpiar sesiones expiradas del sistema
     */
    public function cleanExpiredSessions(): int
    {
        $count = UserSession::cleanExpired();

        if ($count > 0) {
            SecurityEvent::log('expired_sessions_cleaned', null, 'info', [
                'count' => $count,
            ]);
        }

        return $count;
    }

    /**
     * Obtener estadísticas de sesiones
     */
    public function getStats(User $user): array
    {
        return UserSession::getStats($user->id);
    }
}
