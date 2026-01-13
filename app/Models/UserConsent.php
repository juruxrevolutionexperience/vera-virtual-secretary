<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserConsent extends Model
{
    protected $fillable = [
        'user_id',
        'consent_type',
        'consent_version',
        'is_granted',
        'granted_at',
        'revoked_at',
        'ip_address',
        'user_agent',
        'notes',
    ];

    protected $casts = [
        'is_granted' => 'boolean',
        'granted_at' => 'datetime',
        'revoked_at' => 'datetime',
    ];

    /**
     * Usuario propietario del consentimiento
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // ==========================================
    // SCOPES
    // ==========================================

    /**
     * Consentimientos otorgados
     */
    public function scopeGranted($query)
    {
        return $query->where('is_granted', true)
            ->whereNull('revoked_at');
    }

    /**
     * Consentimientos revocados
     */
    public function scopeRevoked($query)
    {
        return $query->where('is_granted', false)
            ->orWhereNotNull('revoked_at');
    }

    /**
     * Filtrar por tipo de consentimiento
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('consent_type', $type);
    }

    /**
     * Filtrar por versión
     */
    public function scopeByVersion($query, string $version)
    {
        return $query->where('consent_version', $version);
    }

    // ==========================================
    // MÉTODOS
    // ==========================================

    /**
     * Otorgar consentimiento
     */
    public function grant(): void
    {
        $this->update([
            'is_granted' => true,
            'granted_at' => now(),
            'revoked_at' => null,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        // Registrar en audit log
        AuditLog::logData(
            'consent_granted',
            "Consentimiento otorgado: {$this->consent_type} v{$this->consent_version}"
        );
    }

    /**
     * Revocar consentimiento
     */
    public function revoke(): void
    {
        $this->update([
            'is_granted' => false,
            'revoked_at' => now(),
        ]);

        // Registrar en audit log
        AuditLog::logData(
            'consent_revoked',
            "Consentimiento revocado: {$this->consent_type} v{$this->consent_version}",
            'warning'
        );
    }

    /**
     * Verificar si está activo
     */
    public function isActive(): bool
    {
        return $this->is_granted && is_null($this->revoked_at);
    }

    // ==========================================
    // MÉTODOS ESTÁTICOS
    // ==========================================

    /**
     * Crear o actualizar consentimiento
     */
    public static function grantConsent(
        int $userId,
        string $consentType,
        string $consentVersion,
        ?string $notes = null
    ): self {
        // Buscar consentimiento existente
        $consent = self::where('user_id', $userId)
            ->where('consent_type', $consentType)
            ->where('consent_version', $consentVersion)
            ->first();

        if ($consent) {
            // Si existe, otorgarlo
            $consent->grant();
            return $consent;
        }

        // Si no existe, crear nuevo
        $consent = self::create([
            'user_id' => $userId,
            'consent_type' => $consentType,
            'consent_version' => $consentVersion,
            'is_granted' => true,
            'granted_at' => now(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'notes' => $notes,
        ]);

        // Registrar en audit log
        AuditLog::logData(
            'consent_granted',
            "Consentimiento otorgado: {$consentType} v{$consentVersion}"
        );

        return $consent;
    }

    /**
     * Verificar si un usuario tiene un consentimiento activo
     */
    public static function hasActiveConsent(int $userId, string $consentType): bool
    {
        return self::where('user_id', $userId)
            ->where('consent_type', $consentType)
            ->where('is_granted', true)
            ->whereNull('revoked_at')
            ->exists();
    }

    /**
     * Tipos de consentimiento disponibles
     */
    public static function consentTypes(): array
    {
        return [
            'terms_of_service' => 'Términos de Servicio',
            'privacy_policy' => 'Política de Privacidad',
            'cookies' => 'Uso de Cookies',
            'marketing' => 'Comunicaciones de Marketing',
            'data_processing' => 'Procesamiento de Datos con IA',
            'analytics' => 'Analytics y Métricas',
        ];
    }
}
