<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScheduledEmail extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'email_account_id',
        'original_email_id',
        'email_template_id',
        'to',
        'cc',
        'bcc',
        'subject',
        'body',
        'body_html',
        'attachments',
        'scheduled_at',
        'status',
        'sent_at',
        'cancelled_at',
        'error_message',
        'retry_count',
        'max_retries',
        'metadata',
    ];

    protected $casts = [
        'attachments' => 'array',
        'metadata' => 'array',
        'scheduled_at' => 'datetime',
        'sent_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'retry_count' => 'integer',
        'max_retries' => 'integer',
    ];

    /**
     * Usuario propietario del email programado
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Cuenta de email desde la que se enviará
     */
    public function emailAccount(): BelongsTo
    {
        return $this->belongsTo(EmailAccount::class);
    }

    /**
     * Email original (si es respuesta/forward)
     */
    public function originalEmail(): BelongsTo
    {
        return $this->belongsTo(Email::class, 'original_email_id');
    }

    /**
     * Template utilizado
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(EmailTemplate::class, 'email_template_id');
    }

    /**
     * Scope: Emails pendientes de envío
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope: Emails listos para enviar (hora programada ya pasó)
     */
    public function scopeReadyToSend($query)
    {
        return $query->where('status', 'pending')
            ->where('scheduled_at', '<=', now());
    }

    /**
     * Scope: Emails que fallaron y pueden reintentarse
     */
    public function scopeFailedRetryable($query)
    {
        return $query->where('status', 'failed')
            ->whereColumn('retry_count', '<', 'max_retries');
    }

    /**
     * Scope: Por usuario
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Marcar como enviado
     */
    public function markAsSent(): void
    {
        $this->update([
            'status' => 'sent',
            'sent_at' => now(),
        ]);
    }

    /**
     * Marcar como fallido
     */
    public function markAsFailed(string $errorMessage): void
    {
        $this->update([
            'status' => 'failed',
            'error_message' => $errorMessage,
        ]);
    }

    /**
     * Marcar como cancelado
     */
    public function markAsCancelled(): void
    {
        $this->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
        ]);
    }

    /**
     * Incrementar contador de reintentos
     */
    public function incrementRetry(): void
    {
        $this->increment('retry_count');
    }

    /**
     * Verificar si puede reintentarse
     */
    public function canRetry(): bool
    {
        return $this->status === 'failed' && $this->retry_count < $this->max_retries;
    }

    /**
     * Verificar si está listo para enviar
     */
    public function isReadyToSend(): bool
    {
        return $this->status === 'pending' && $this->scheduled_at <= now();
    }

    /**
     * Obtener destinatarios como array
     */
    public function getToArray(): array
    {
        return array_map('trim', explode(',', $this->to));
    }

    /**
     * Obtener CC como array
     */
    public function getCcArray(): array
    {
        if (!$this->cc) {
            return [];
        }
        return array_map('trim', explode(',', $this->cc));
    }

    /**
     * Obtener BCC como array
     */
    public function getBccArray(): array
    {
        if (!$this->bcc) {
            return [];
        }
        return array_map('trim', explode(',', $this->bcc));
    }
}
