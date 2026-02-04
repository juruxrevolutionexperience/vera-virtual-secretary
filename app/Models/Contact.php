<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Contact extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'email',
        'name',
        'company',
        'phone',
        'notes',
        'emails_received',
        'emails_sent',
        'first_contact_at',
        'last_contact_at',
        'priority',
        'is_starred',
        'is_blocked',
        'metadata',
    ];

    protected $casts = [
        'emails_received' => 'integer',
        'emails_sent' => 'integer',
        'first_contact_at' => 'datetime',
        'last_contact_at' => 'datetime',
        'is_starred' => 'boolean',
        'is_blocked' => 'boolean',
        'metadata' => 'array',
    ];

    // ==========================================
    // RELACIONES
    // ==========================================

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Obtener emails recibidos de este contacto
     */
    public function getReceivedEmailsAttribute()
    {
        return Email::where('user_id', $this->user_id)
            ->where('from_email', $this->email)
            ->orderBy('received_at', 'desc')
            ->get();
    }

    /**
     * Obtener emails enviados a este contacto
     */
    public function getSentEmailsAttribute()
    {
        return Email::where('user_id', $this->user_id)
            ->where('to_email', $this->email)
            ->orderBy('received_at', 'desc')
            ->get();
    }

    /**
     * Obtener todos los emails relacionados con este contacto
     */
    public function getAllEmailsAttribute()
    {
        return Email::where('user_id', $this->user_id)
            ->where(function ($query) {
                $query->where('from_email', $this->email)
                    ->orWhere('to_email', $this->email);
            })
            ->orderBy('received_at', 'desc')
            ->get();
    }

    // ==========================================
    // SCOPES
    // ==========================================

    public function scopeStarred($query)
    {
        return $query->where('is_starred', true);
    }

    public function scopeBlocked($query)
    {
        return $query->where('is_blocked', true);
    }

    public function scopeNotBlocked($query)
    {
        return $query->where('is_blocked', false);
    }

    public function scopeByPriority($query, string $priority)
    {
        return $query->where('priority', $priority);
    }

    public function scopeVip($query)
    {
        return $query->where('priority', 'vip');
    }

    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('last_contact_at', '>=', now()->subDays($days));
    }

    public function scopeSearch($query, string $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")
                ->orWhere('company', 'like', "%{$search}%")
                ->orWhere('phone', 'like', "%{$search}%");
        });
    }

    // ==========================================
    // MÉTODOS DE UTILIDAD
    // ==========================================

    /**
     * Incrementar contador de emails recibidos
     */
    public function incrementReceived(): void
    {
        $this->increment('emails_received');
        $this->updateLastContact();
    }

    /**
     * Incrementar contador de emails enviados
     */
    public function incrementSent(): void
    {
        $this->increment('emails_sent');
        $this->updateLastContact();
    }

    /**
     * Actualizar última fecha de contacto
     */
    public function updateLastContact(): void
    {
        $updates = ['last_contact_at' => now()];

        // Si es el primer contacto, establecer first_contact_at
        if (!$this->first_contact_at) {
            $updates['first_contact_at'] = now();
        }

        $this->update($updates);
    }

    /**
     * Marcar/desmarcar como estrella
     */
    public function toggleStar(): void
    {
        $this->update(['is_starred' => !$this->is_starred]);
    }

    /**
     * Bloquear contacto
     */
    public function block(): void
    {
        $this->update(['is_blocked' => true]);
    }

    /**
     * Desbloquear contacto
     */
    public function unblock(): void
    {
        $this->update(['is_blocked' => false]);
    }

    /**
     * Establecer prioridad
     */
    public function setPriority(string $priority): void
    {
        $this->update(['priority' => $priority]);
    }

    /**
     * Obtener total de interacciones
     */
    public function getTotalInteractionsAttribute(): int
    {
        return $this->emails_received + $this->emails_sent;
    }

    /**
     * Verificar si es un contacto frecuente (más de X interacciones)
     */
    public function isFrequent(int $threshold = 10): bool
    {
        return $this->total_interactions >= $threshold;
    }

    /**
     * Verificar si es un contacto reciente (últimos X días)
     */
    public function isRecent(int $days = 30): bool
    {
        return $this->last_contact_at &&
               $this->last_contact_at->isAfter(now()->subDays($days));
    }

    /**
     * Verificar si es un contacto inactivo (sin contacto en X días)
     */
    public function isInactive(int $days = 90): bool
    {
        return !$this->last_contact_at ||
               $this->last_contact_at->isBefore(now()->subDays($days));
    }
}
