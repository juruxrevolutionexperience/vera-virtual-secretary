<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FollowUpReminder extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'email_id',
        'title',
        'notes',
        'remind_at',
        'is_recurring',
        'recurrence_type',
        'recurrence_interval',
        'recurrence_end_date',
        'status',
        'sent_at',
        'completed_at',
        'cancelled_at',
        'notification_channels',
        'metadata',
    ];

    protected $casts = [
        'remind_at' => 'datetime',
        'is_recurring' => 'boolean',
        'recurrence_interval' => 'integer',
        'recurrence_end_date' => 'datetime',
        'sent_at' => 'datetime',
        'completed_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'notification_channels' => 'array',
        'metadata' => 'array',
    ];

    protected $attributes = [
        'notification_channels' => '["in_app"]',  // Valor por defecto
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

    public function scopeSent($query)
    {
        return $query->where('status', 'sent');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }

    public function scopeReadyToSend($query)
    {
        return $query->where('status', 'pending')
            ->where('remind_at', '<=', now());
    }

    public function scopeRecurring($query)
    {
        return $query->where('is_recurring', true);
    }

    public function scopeUpcoming($query, int $days = 7)
    {
        return $query->where('status', 'pending')
            ->whereBetween('remind_at', [now(), now()->addDays($days)])
            ->orderBy('remind_at');
    }

    public function scopeOverdue($query)
    {
        return $query->where('status', 'pending')
            ->where('remind_at', '<', now());
    }

    // ==========================================
    // MÉTODOS DE GESTIÓN DE ESTADO
    // ==========================================

    /**
     * Marcar recordatorio como enviado
     */
    public function markAsSent(): void
    {
        $this->update([
            'status' => 'sent',
            'sent_at' => now(),
        ]);

        // Si es recurrente, crear el siguiente recordatorio
        if ($this->is_recurring) {
            $this->createNextRecurrence();
        }
    }

    /**
     * Marcar recordatorio como completado
     */
    public function markAsCompleted(): void
    {
        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);
    }

    /**
     * Cancelar recordatorio
     */
    public function cancel(): void
    {
        $this->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
        ]);
    }

    /**
     * Reactivar recordatorio cancelado
     */
    public function reactivate(): void
    {
        $this->update([
            'status' => 'pending',
            'cancelled_at' => null,
        ]);
    }

    /**
     * Posponer recordatorio
     */
    public function snooze(int $minutes): void
    {
        $this->update([
            'remind_at' => now()->addMinutes($minutes),
        ]);
    }

    // ==========================================
    // MÉTODOS DE RECURRENCIA
    // ==========================================

    /**
     * Crear siguiente ocurrencia del recordatorio recurrente
     */
    protected function createNextRecurrence(): void
    {
        // Verificar si no ha terminado el periodo de recurrencia
        if ($this->recurrence_end_date && now()->isAfter($this->recurrence_end_date)) {
            return;
        }

        // Calcular próxima fecha de recordatorio
        $nextRemindAt = $this->calculateNextRemindDate();

        // Crear nuevo recordatorio
        static::create([
            'user_id' => $this->user_id,
            'email_id' => $this->email_id,
            'title' => $this->title,
            'notes' => $this->notes,
            'remind_at' => $nextRemindAt,
            'is_recurring' => true,
            'recurrence_type' => $this->recurrence_type,
            'recurrence_interval' => $this->recurrence_interval,
            'recurrence_end_date' => $this->recurrence_end_date,
            'notification_channels' => $this->notification_channels,
            'metadata' => $this->metadata,
        ]);
    }

    /**
     * Calcular siguiente fecha de recordatorio
     */
    protected function calculateNextRemindDate(): \Carbon\Carbon
    {
        $interval = $this->recurrence_interval ?? 1;

        return match($this->recurrence_type) {
            'daily' => $this->remind_at->addDays($interval),
            'weekly' => $this->remind_at->addWeeks($interval),
            'monthly' => $this->remind_at->addMonths($interval),
            default => $this->remind_at->addDays($interval),
        };
    }

    // ==========================================
    // MÉTODOS DE VERIFICACIÓN
    // ==========================================

    /**
     * Verificar si el recordatorio está listo para enviarse
     */
    public function isReadyToSend(): bool
    {
        return $this->status === 'pending' && $this->remind_at->isPast();
    }

    /**
     * Verificar si está vencido
     */
    public function isOverdue(): bool
    {
        return $this->status === 'pending' && $this->remind_at->isPast();
    }

    /**
     * Verificar si es próximo (en las próximas X horas)
     */
    public function isUpcoming(int $hours = 24): bool
    {
        return $this->status === 'pending' &&
               $this->remind_at->isFuture() &&
               $this->remind_at->diffInHours(now()) <= $hours;
    }

    /**
     * Verificar si debe notificarse por email
     */
    public function shouldNotifyByEmail(): bool
    {
        return in_array('email', $this->notification_channels ?? []);
    }

    /**
     * Verificar si debe notificarse en la app
     */
    public function shouldNotifyInApp(): bool
    {
        return in_array('in_app', $this->notification_channels ?? []);
    }

    /**
     * Verificar si debe notificarse por push
     */
    public function shouldNotifyByPush(): bool
    {
        return in_array('push', $this->notification_channels ?? []);
    }

    // ==========================================
    // MÉTODOS DE UTILIDAD
    // ==========================================

    /**
     * Obtener descripción del recordatorio
     */
    public function getDescriptionAttribute(): string
    {
        $time = $this->remind_at->diffForHumans();
        return "{$this->title} - {$time}";
    }

    /**
     * Obtener clase CSS según estado
     */
    public function getStatusClassAttribute(): string
    {
        return match($this->status) {
            'pending' => $this->isOverdue() ? 'text-red-600' : 'text-blue-600',
            'sent' => 'text-gray-600',
            'completed' => 'text-green-600',
            'cancelled' => 'text-gray-400',
            default => 'text-gray-600',
        };
    }

    /**
     * Obtener ícono según estado
     */
    public function getIconAttribute(): string
    {
        return match($this->status) {
            'pending' => $this->isOverdue() ? '🔴' : '⏰',
            'sent' => '✉️',
            'completed' => '✅',
            'cancelled' => '❌',
            default => '⏰',
        };
    }
}
