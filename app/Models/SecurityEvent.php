<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SecurityEvent extends Model
{
    protected $fillable = [
        'user_id',
        'event_type',
        'severity',
        'ip_address',
        'user_agent',
        'metadata',
        'requires_action',
        'actioned_at',
        'actioned_by',
    ];

    protected $casts = [
        'metadata' => 'array',
        'requires_action' => 'boolean',
        'actioned_at' => 'datetime',
    ];

    // ==========================================
    // RELATIONSHIPS
    // ==========================================

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function actionedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actioned_by');
    }

    // ==========================================
    // SCOPES
    // ==========================================

    public function scopeCritical($query)
    {
        return $query->where('severity', 'critical');
    }

    public function scopePending($query)
    {
        return $query->where('requires_action', true)
            ->whereNull('actioned_at');
    }

    // ==========================================
    // MÉTODOS ESTÁTICOS
    // ==========================================

    /**
     * Registrar evento de seguridad
     */
    public static function log(
        string $eventType,
        ?int $userId = null,
        string $severity = 'info',
        ?array $metadata = null
    ): self {
        return static::create([
            'user_id' => $userId,
            'event_type' => $eventType,
            'severity' => $severity,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'metadata' => $metadata,
            'requires_action' => in_array($severity, ['danger', 'critical']),
        ]);
    }
}
