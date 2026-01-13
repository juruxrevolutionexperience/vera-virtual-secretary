<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AuditLog extends Model
{
    protected $fillable = [
        'user_id',
        'organization_id',
        'event_type',
        'event_category',
        'auditable_type',
        'auditable_id',
        'description',
        'old_values',
        'new_values',
        'metadata',
        'ip_address',
        'user_agent',
        'action_result',
        'severity',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'metadata' => 'array',
        'created_at' => 'datetime',
    ];

    /**
     * Usuario que realizó la acción
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Organización relacionada
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Modelo afectado (polimórfico)
     */
    public function auditable(): MorphTo
    {
        return $this->morphTo();
    }

    // ==========================================
    // SCOPES
    // ==========================================

    /**
     * Filtrar por tipo de evento
     */
    public function scopeByEventType($query, string $eventType)
    {
        return $query->where('event_type', $eventType);
    }

    /**
     * Filtrar por categoría
     */
    public function scopeByCategory($query, string $category)
    {
        return $query->where('event_category', $category);
    }

    /**
     * Filtrar por severidad
     */
    public function scopeBySeverity($query, string $severity)
    {
        return $query->where('severity', $severity);
    }

    /**
     * Filtrar por usuario
     */
    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Filtrar por organización
     */
    public function scopeByOrganization($query, int $organizationId)
    {
        return $query->where('organization_id', $organizationId);
    }

    /**
     * Solo eventos críticos
     */
    public function scopeCritical($query)
    {
        return $query->where('severity', 'critical');
    }

    /**
     * Solo eventos fallidos
     */
    public function scopeFailed($query)
    {
        return $query->where('action_result', 'failed');
    }

    /**
     * Eventos de un rango de fechas
     */
    public function scopeDateRange($query, $from, $to)
    {
        return $query->whereBetween('created_at', [$from, $to]);
    }

    // ==========================================
    // MÉTODOS ESTÁTICOS PARA LOG
    // ==========================================

    /**
     * Crear log de auditoría
     */
    public static function createLog(array $data): self
    {
        // Agregar automáticamente IP y User Agent si no se proporcionan
        if (!isset($data['ip_address'])) {
            $data['ip_address'] = request()->ip();
        }

        if (!isset($data['user_agent'])) {
            $data['user_agent'] = request()->userAgent();
        }

        // Si no hay user_id y hay usuario autenticado, agregarlo
        if (!isset($data['user_id']) && auth()->check()) {
            $data['user_id'] = auth()->id();
        }

        return self::create($data);
    }

    /**
     * Log de autenticación
     */
    public static function logAuth(string $eventType, string $description, string $result = 'success'): self
    {
        return self::createLog([
            'event_type' => $eventType,
            'event_category' => 'auth',
            'description' => $description,
            'action_result' => $result,
            'severity' => $result === 'failed' ? 'warning' : 'info',
        ]);
    }

    /**
     * Log de email
     */
    public static function logEmail(string $eventType, string $description, ?Email $email = null): self
    {
        return self::createLog([
            'event_type' => $eventType,
            'event_category' => 'email',
            'description' => $description,
            'auditable_type' => $email ? Email::class : null,
            'auditable_id' => $email?->id,
            'severity' => 'info',
        ]);
    }

    /**
     * Log de configuración
     */
    public static function logSettings(string $description, ?array $oldValues = null, ?array $newValues = null): self
    {
        return self::createLog([
            'event_type' => 'settings_changed',
            'event_category' => 'settings',
            'description' => $description,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'severity' => 'info',
        ]);
    }

    /**
     * Log de seguridad
     */
    public static function logSecurity(string $eventType, string $description, string $severity = 'warning'): self
    {
        return self::createLog([
            'event_type' => $eventType,
            'event_category' => 'security',
            'description' => $description,
            'severity' => $severity,
        ]);
    }

    /**
     * Log de datos (RGPD)
     */
    public static function logData(string $eventType, string $description, string $severity = 'info'): self
    {
        return self::createLog([
            'event_type' => $eventType,
            'event_category' => 'data',
            'description' => $description,
            'severity' => $severity,
        ]);
    }
}
