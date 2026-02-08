<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Jenssegers\Agent\Agent;

class UserSession extends Model
{
    protected $fillable = [
        'user_id',
        'session_id',
        'ip_address',
        'user_agent',
        'device_type',
        'browser',
        'platform',
        'location',
        'last_activity',
        'expires_at',
        'is_current',
    ];

    protected $casts = [
        'last_activity' => 'datetime',
        'expires_at' => 'datetime',
        'is_current' => 'boolean',
    ];

    /**
     * Relación con User
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Parsear user agent y extraer información
     */
    public static function parseUserAgent(string $userAgent): array
    {
        $agent = new Agent();
        $agent->setUserAgent($userAgent);

        return [
            'device_type' => $agent->isDesktop() ? 'desktop' : ($agent->isTablet() ? 'tablet' : 'mobile'),
            'browser' => $agent->browser(),
            'platform' => $agent->platform(),
        ];
    }

    /**
     * Verificar si la sesión está activa
     */
    public function isActive(): bool
    {
        return $this->expires_at === null || $this->expires_at->isFuture();
    }

    /**
     * Verificar si la sesión está expirada
     */
    public function isExpired(): bool
    {
        return !$this->isActive();
    }

    /**
     * Obtener icono del dispositivo
     */
    public function getDeviceIconAttribute(): string
    {
        return match($this->device_type) {
            'desktop' => '💻',
            'tablet' => '📱',
            'mobile' => '📱',
            default => '🖥️',
        };
    }

    /**
     * Obtener color del badge según estado
     */
    public function getBadgeColorAttribute(): string
    {
        if ($this->is_current) {
            return 'green'; // Sesión actual
        }

        if ($this->isExpired()) {
            return 'gray'; // Expirada
        }

        return 'blue'; // Activa
    }

    /**
     * Scope: Sesiones activas
     */
    public function scopeActive($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('expires_at')
                ->orWhere('expires_at', '>', now());
        })->whereNull('revoked_at');
    }

    /**
     * Revocar esta sesión
     */
    public function revoke(string $reason = 'manual'): void
    {
        $this->update([
            'revoked_at' => now(),
            'revoked_reason' => $reason,
        ]);
    }

    /**
     * Revocar todas las sesiones excepto la actual
     */
    public static function revokeAllExceptCurrent(int $userId, string $currentSessionId): int
    {
        return static::where('user_id', $userId)
            ->where('session_id', '!=', $currentSessionId)
            ->whereNull('revoked_at')
            ->update([
                'revoked_at' => now(),
                'revoked_reason' => 'revoked_all',
            ]);
    }

    /**
     * Detectar actividad sospechosa
     */
    public static function detectSuspiciousActivity(int $userId): array
    {
        $suspicious = [];
        $sessions = static::where('user_id', $userId)->active()->get();

        // Múltiples IPs en poco tiempo
        $uniqueIps = $sessions->pluck('ip_address')->unique();
        if ($uniqueIps->count() > 3) {
            $suspicious[] = [
                'type' => 'multiple_ips',
                'message' => 'Múltiples IPs detectadas en sesiones activas',
                'severity' => 'medium',
            ];
        }

        // Múltiples ubicaciones
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

    /**
     * Limpiar sesiones expiradas
     */
    public static function cleanExpired(): int
    {
        return static::where('expires_at', '<', now()->subDays(7))
            ->orWhere('revoked_at', '<', now()->subDays(30))
            ->delete();
    }

    /**
     * Obtener estadísticas de sesiones
     */
    public static function getStats(int $userId): array
    {
        $sessions = static::where('user_id', $userId);

        return [
            'total' => $sessions->count(),
            'active' => $sessions->active()->count(),
            'trusted' => $sessions->where('is_trusted', true)->count(),
            'platforms' => $sessions->groupBy('platform')->selectRaw('platform, count(*) as count')->pluck('count', 'platform')->toArray(),
        ];
    }

    /**
     * Atributo: device_info
     */
    public function getDeviceInfoAttribute(): string
    {
        return "{$this->device_icon} {$this->browser} en {$this->platform}";
    }

    /**
     * Atributo: location_info
     */
    public function getLocationInfoAttribute(): string
    {
        if ($this->city && $this->country) {
            return "{$this->city}, {$this->country}";
        }

        if ($this->location) {
            return $this->location;
        }

        return $this->ip_address ?? 'Desconocido';
    }
}
