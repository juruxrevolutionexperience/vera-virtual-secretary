<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserSession;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class SessionManagementService
{
    /**
     * Registrar nueva sesión
     */
    public function recordSession(User $user, Request $request): UserSession
    {
        $sessionId = Session::getId();
        $userAgent = $request->userAgent() ?? 'Unknown';
        $parsedAgent = UserSession::parseUserAgent($userAgent);

        // Marcar todas las sesiones anteriores como no actuales
        UserSession::where('user_id', $user->id)
            ->update(['is_current' => false]);

        return UserSession::create([
            'user_id' => $user->id,
            'session_id' => $sessionId,
            'ip_address' => $request->ip(),
            'user_agent' => $userAgent,
            'device_type' => $parsedAgent['device_type'],
            'browser' => $parsedAgent['browser'],
            'platform' => $parsedAgent['platform'],
            'location' => $this->getLocationFromIp($request->ip()),
            'last_activity' => now(),
            'expires_at' => now()->addMinutes(config('session.lifetime', 120)),
            'is_current' => true,
        ]);
    }

    /**
     * Actualizar actividad de la sesión actual
     */
    public function updateActivity(string $sessionId): void
    {
        UserSession::where('session_id', $sessionId)
            ->update([
                'last_activity' => now(),
                'expires_at' => now()->addMinutes(config('session.lifetime', 120)),
            ]);
    }

    /**
     * Obtener sesiones activas de un usuario
     */
    public function getActiveSessions(User $user)
    {
        return UserSession::where('user_id', $user->id)
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->orderBy('last_activity', 'desc')
            ->get();
    }

    /**
     * Obtener todas las sesiones de un usuario (activas + expiradas)
     */
    public function getAllSessions(User $user)
    {
        return UserSession::where('user_id', $user->id)
            ->orderBy('last_activity', 'desc')
            ->get();
    }

    /**
     * Cerrar sesión específica
     */
    public function terminateSession(User $user, int $sessionId): bool
    {
        $session = UserSession::where('id', $sessionId)
            ->where('user_id', $user->id)
            ->first();

        if (!$session) {
            return false;
        }

        // Si es la sesión actual, no permitir cerrarla (debe usar logout normal)
        if ($session->is_current) {
            return false;
        }

        // Marcar como expirada
        $session->update(['expires_at' => now()]);

        // Eliminar de la tabla de sesiones de Laravel
        DB::table(config('session.table', 'sessions'))
            ->where('id', $session->session_id)
            ->delete();

        return true;
    }

    /**
     * Cerrar todas las sesiones excepto la actual
     */
    public function terminateOtherSessions(User $user, string $currentSessionId): int
    {
        $sessions = UserSession::where('user_id', $user->id)
            ->where('session_id', '!=', $currentSessionId)
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->get();

        foreach ($sessions as $session) {
            $session->update(['expires_at' => now()]);

            DB::table(config('session.table', 'sessions'))
                ->where('id', $session->session_id)
                ->delete();
        }

        return $sessions->count();
    }

    /**
     * Limpiar sesiones expiradas (ejecutar por cron)
     */
    public function cleanExpiredSessions(): int
    {
        $deleted = UserSession::where('expires_at', '<', now()->subDays(7))
            ->delete();

        return $deleted;
    }

    /**
     * Obtener ubicación aproximada desde IP (usando servicio externo)
     */
    protected function getLocationFromIp(string $ip): ?string
    {
        // Para localhost/desarrollo
        if (in_array($ip, ['127.0.0.1', '::1', 'localhost'])) {
            return 'Local Development';
        }

        // TODO: Integrar con servicio como ipinfo.io, ipapi.co, etc.
        // Por ahora retornamos null
        return null;
    }

    /**
     * Detectar sesión sospechosa (cambio de ubicación repentino, etc.)
     */
    public function detectSuspiciousActivity(User $user): array
    {
        $suspicious = [];
        $sessions = $this->getActiveSessions($user);

        // Si hay sesiones desde IPs muy diferentes en poco tiempo
        $uniqueIps = $sessions->pluck('ip_address')->unique();
        if ($uniqueIps->count() > 3) {
            $suspicious[] = [
                'type' => 'multiple_ips',
                'message' => 'Múltiples IPs detectadas en sesiones activas',
                'severity' => 'medium',
            ];
        }

        // Si hay sesiones desde diferentes países
        $locations = $sessions->pluck('location')->filter()->unique();
        if ($locations->count() > 2) {
            $suspicious[] = [
                'type' => 'multiple_locations',
                'message' => 'Sesiones activas desde múltiples ubicaciones',
                'severity' => 'high',
            ];
        }

        return $suspicious;
    }
}
