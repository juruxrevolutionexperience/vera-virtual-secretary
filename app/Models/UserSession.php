<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Casts\Attribute;

class UserSession extends Model
{
    protected $fillable = [
        'user_id',
        'session_id',
        'ip_address',
        'user_agent',
        'device_type',
        'device_name',
        'platform',
        'browser',
        'country',
        'city',
        'is_current',
        'is_trusted',
        'last_activity',
        'expires_at',
        'revoked_at',
        'revoked_reason',
    ];

    protected $casts = [
        'is_current' => 'boolean',
        'is_trusted' => 'boolean',
        'last_activity' => 'datetime',
        'expires_at' => 'datetime',
        'revoked_at' => 'datetime',
    ];

    // ==========================================
    // RELATIONSHIPS
    // ==========================================

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // ==========================================
    // SCOPES
    // ==========================================

    /**
     * Sesiones activas (no revocadas y no expiradas)
     */
    public function scopeActive($query)
    {
        return $query->whereNull('revoked_at')
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });
    }

    /**
     * Sesiones revocadas
     */
    public function scopeRevoked($query)
    {
        return $query->whereNotNull('revoked_at');
    }

    /**
     * Sesiones expiradas
     */
    public function scopeExpired($query)
    {
        return $query->whereNull('revoked_at')
            ->where('expires_at', '<=', now());
    }

    /**
     * Sesiones de un dispositivo específico
     */
    public function scopeByDevice($query, string $deviceType)
    {
        return $query->where('device_type', $deviceType);
    }

    /**
     * Sesiones confiables
     */
    public function scopeTrusted($query)
    {
        return $query->where('is_trusted', true);
    }

    /**
     * Sesiones actuales (la sesión activa del usuario)
     */
    public function scopeCurrent($query)
    {
        return $query->where('is_current', true);
    }

    // ==========================================
    // ACCESSORS
    // ==========================================

    /**
     * Nombre legible del dispositivo
     */
    protected function deviceInfo(): Attribute
    {
        return Attribute::make(
            get: fn() => sprintf(
                '%s on %s (%s)',
                $this->browser ?? 'Unknown Browser',
                $this->device_name ?? $this->platform ?? 'Unknown Device',
                $this->device_type ?? 'unknown'
            ),
        );
    }

    /**
     * Ubicación legible
     */
    protected function locationInfo(): Attribute
    {
        return Attribute::make(
            get: function () {
                $parts = array_filter([$this->city, $this->country]);
                return !empty($parts) ? implode(', ', $parts) : 'Unknown Location';
            },
        );
    }

    /**
     * Verifica si la sesión está activa
     */
    protected function isActive(): Attribute
    {
        return Attribute::make(
            get: function () {
                if ($this->revoked_at) {
                    return false;
                }

                if ($this->expires_at && $this->expires_at <= now()) {
                    return false;
                }

                return true;
            },
        );
    }

    // ==========================================
    // MÉTODOS ESTÁTICOS
    // ==========================================

    /**
     * Crear o actualizar sesión del usuario actual
     */
    public static function trackSession(
        int $userId,
        string $sessionId,
        string $ipAddress,
        string $userAgent
    ): self {
        $agent = new \Jenssegers\Agent\Agent();
        $agent->setUserAgent($userAgent);

        // Buscar sesión existente o crear nueva
        $session = static::firstOrNew([
            'user_id' => $userId,
            'session_id' => $sessionId,
        ]);

        $session->fill([
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'device_type' => static::getDeviceType($agent),
            'device_name' => $agent->device(),
            'platform' => $agent->platform(),
            'browser' => $agent->browser(),
            'is_current' => true,
            'last_activity' => now(),
            'expires_at' => now()->addMinutes(config('session.lifetime', 120)),
        ]);

        // Marcar todas las demás sesiones del usuario como no actuales
        if ($session->exists) {
            static::where('user_id', $userId)
                ->where('id', '!=', $session->id)
                ->update(['is_current' => false]);
        }

        $session->save();

        // Marcar las demás sesiones como no actuales
        if (!$session->wasRecentlyCreated) {
            static::where('user_id', $userId)
                ->where('id', '!=', $session->id)
                ->update(['is_current' => false]);
        }

        return $session;
    }

    /**
     * Obtener tipo de dispositivo
     */
    private static function getDeviceType(\Jenssegers\Agent\Agent $agent): string
    {
        if ($agent->isRobot()) {
            return 'robot';
        }
        if ($agent->isTablet()) {
            return 'tablet';
        }
        if ($agent->isMobile()) {
            return 'mobile';
        }
        if ($agent->isDesktop()) {
            return 'desktop';
        }

        return 'unknown';
    }

    /**
     * Revocar sesión
     */
    public function revoke(string $reason = 'manual'): void
    {
        $this->update([
            'revoked_at' => now(),
            'revoked_reason' => $reason,
            'is_current' => false,
        ]);
    }

    /**
     * Revocar todas las sesiones del usuario excepto la actual
     */
    public static function revokeAllExceptCurrent(int $userId, ?string $currentSessionId = null): int
    {
        $query = static::where('user_id', $userId)
            ->active();

        if ($currentSessionId) {
            $query->where('session_id', '!=', $currentSessionId);
        } else {
            $query->where('is_current', false);
        }

        return $query->update([
            'revoked_at' => now(),
            'revoked_reason' => 'logout_all',
            'is_current' => false,
        ]);
    }

    /**
     * Limpiar sesiones expiradas (para command)
     */
    public static function cleanExpired(): int
    {
        return static::expired()
            ->whereNull('revoked_at')
            ->update([
                'revoked_at' => now(),
                'revoked_reason' => 'expired',
            ]);
    }

    /**
     * Obtener estadísticas de sesiones de un usuario
     */
    public static function getStats(int $userId): array
    {
        $sessions = static::where('user_id', $userId)->get();

        return [
            'total' => $sessions->count(),
            'active' => $sessions->filter->is_active->count(),
            'revoked' => $sessions->filter(fn($s) => $s->revoked_at !== null)->count(),
            'expired' => $sessions->filter(fn($s) => !$s->is_active && !$s->revoked_at)->count(),
            'trusted' => $sessions->filter->is_trusted->count(),
            'by_device' => $sessions->groupBy('device_type')->map->count(),
            'by_platform' => $sessions->groupBy('platform')->map->count(),
        ];
    }

    /**
     * Detectar sesiones sospechosas (múltiples ubicaciones/dispositivos en corto tiempo)
     */
    public static function detectSuspiciousActivity(int $userId, int $minutesWindow = 30): array
    {
        $recentSessions = static::where('user_id', $userId)
            ->where('created_at', '>=', now()->subMinutes($minutesWindow))
            ->orderBy('created_at', 'desc')
            ->get();

        $suspicious = [];

        // Múltiples países en corto tiempo
        $countries = $recentSessions->pluck('country')->filter()->unique();
        if ($countries->count() > 1) {
            $suspicious[] = [
                'type' => 'multiple_countries',
                'message' => "Login desde {$countries->count()} países diferentes en {$minutesWindow} minutos",
                'countries' => $countries->values()->toArray(),
            ];
        }

        // Múltiples dispositivos en corto tiempo
        $devices = $recentSessions->pluck('device_type')->filter()->unique();
        if ($devices->count() > 2) {
            $suspicious[] = [
                'type' => 'multiple_devices',
                'message' => "Login desde {$devices->count()} tipos de dispositivos en {$minutesWindow} minutos",
                'devices' => $devices->values()->toArray(),
            ];
        }

        return $suspicious;
    }
}
