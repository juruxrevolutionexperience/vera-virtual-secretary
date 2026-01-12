<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailAlert extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'email_id',
        'type',
        'severity',
        'title',
        'message',
        'metadata',
        'status',
        'acknowledged_at',
        'resolved_at',
        'dismissed_at',
        'resolution_notes',
        'alert_at',
        'expires_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'acknowledged_at' => 'datetime',
        'resolved_at' => 'datetime',
        'dismissed_at' => 'datetime',
        'alert_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    // ==========================================
    // RELACIONES
    // ==========================================

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function email(): BelongsTo
    {
        return $this->belongsTo(Email::class);
    }

    // ==========================================
    // SCOPES
    // ==========================================

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeAcknowledged($query)
    {
        return $query->where('status', 'acknowledged');
    }

    public function scopeResolved($query)
    {
        return $query->where('status', 'resolved');
    }

    public function scopeDismissed($query)
    {
        return $query->where('status', 'dismissed');
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', ['pending', 'acknowledged']);
    }

    public function scopeBySeverity($query, string $severity)
    {
        return $query->where('severity', $severity);
    }

    public function scopeCritical($query)
    {
        return $query->where('severity', 'critical');
    }

    public function scopeHigh($query)
    {
        return $query->where('severity', 'high');
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeReadyToShow($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('alert_at')
                ->orWhere('alert_at', '<=', now());
        })
        ->where(function ($q) {
            $q->whereNull('expires_at')
                ->orWhere('expires_at', '>', now());
        });
    }

    public function scopeExpired($query)
    {
        return $query->whereNotNull('expires_at')
            ->where('expires_at', '<=', now());
    }

    // ==========================================
    // MÉTODOS DE GESTIÓN DE ESTADO
    // ==========================================

    /**
     * Marcar alerta como reconocida
     */
    public function acknowledge(): void
    {
        $this->update([
            'status' => 'acknowledged',
            'acknowledged_at' => now(),
        ]);
    }

    /**
     * Marcar alerta como resuelta
     */
    public function resolve(string $notes = null): void
    {
        $this->update([
            'status' => 'resolved',
            'resolved_at' => now(),
            'resolution_notes' => $notes,
        ]);
    }

    /**
     * Descartar alerta
     */
    public function dismiss(): void
    {
        $this->update([
            'status' => 'dismissed',
            'dismissed_at' => now(),
        ]);
    }

    /**
     * Reabrir alerta
     */
    public function reopen(): void
    {
        $this->update([
            'status' => 'pending',
            'acknowledged_at' => null,
            'resolved_at' => null,
            'dismissed_at' => null,
        ]);
    }

    // ==========================================
    // MÉTODOS DE VERIFICACIÓN
    // ==========================================

    /**
     * Verificar si la alerta está activa
     */
    public function isActive(): bool
    {
        return in_array($this->status, ['pending', 'acknowledged']);
    }

    /**
     * Verificar si la alerta debe mostrarse ahora
     */
    public function shouldShow(): bool
    {
        // No mostrar si está resuelta o descartada
        if (!$this->isActive()) {
            return false;
        }

        // No mostrar si aún no es tiempo
        if ($this->alert_at && $this->alert_at->isFuture()) {
            return false;
        }

        // No mostrar si ha expirado
        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }

        return true;
    }

    /**
     * Verificar si la alerta ha expirado
     */
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Verificar si es crítica
     */
    public function isCritical(): bool
    {
        return $this->severity === 'critical';
    }

    /**
     * Verificar si es de alta prioridad
     */
    public function isHigh(): bool
    {
        return $this->severity === 'high';
    }

    // ==========================================
    // MÉTODOS DE UTILIDAD
    // ==========================================

    /**
     * Obtener clase CSS según severidad
     */
    public function getSeverityClassAttribute(): string
    {
        return match($this->severity) {
            'critical' => 'bg-red-100 text-red-800 border-red-500',
            'high' => 'bg-orange-100 text-orange-800 border-orange-500',
            'medium' => 'bg-yellow-100 text-yellow-800 border-yellow-500',
            'low' => 'bg-blue-100 text-blue-800 border-blue-500',
            default => 'bg-gray-100 text-gray-800 border-gray-500',
        };
    }

    /**
     * Obtener ícono según tipo
     */
    public function getIconAttribute(): string
    {
        return match($this->type) {
            'deadline' => '⏰',
            'no_response' => '❌',
            'follow_up' => '🔔',
            'escalation' => '⚠️',
            'overdue' => '🚨',
            'high_priority' => '🔴',
            'custom' => '📌',
            default => '🔔',
        };
    }
}
