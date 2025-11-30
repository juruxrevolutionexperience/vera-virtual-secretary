<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SyncLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'email_account_id', 'sync_type', 'direction', 'status',
        'emails_fetched', 'emails_created', 'emails_updated', 'emails_failed',
        'started_at', 'completed_at', 'duration_seconds',
        'error_message', 'error_details', 'metadata',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'error_details' => 'array',
        'metadata' => 'array',
        'emails_fetched' => 'integer',
        'emails_created' => 'integer',
        'emails_updated' => 'integer',
        'emails_failed' => 'integer',
        'duration_seconds' => 'integer',
    ];

    /**
     * Cuenta de email asociada
     */
    public function emailAccount(): BelongsTo
    {
        return $this->belongsTo(EmailAccount::class);
    }

    /**
     * Scope por estado
     */
    public function scopeStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope para logs exitosos
     */
    public function scopeSuccessful($query)
    {
        return $query->where('status', 'success');
    }

    /**
     * Scope para logs fallidos
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Scope por tipo de sincronización
     */
    public function scopeType($query, string $type)
    {
        return $query->where('sync_type', $type);
    }

    /**
     * Verificar si fue exitoso
     */
    public function wasSuccessful(): bool
    {
        return $this->status === 'success';
    }

    /**
     * Obtener duración formateada
     */
    public function getDurationFormattedAttribute(): string
    {
        if (!$this->duration_seconds) return 'N/A';

        $minutes = floor($this->duration_seconds / 60);
        $seconds = $this->duration_seconds % 60;

        if ($minutes > 0) {
            return "{$minutes}m {$seconds}s";
        }

        return "{$seconds}s";
    }

    /**
     * Calcular y guardar duración
     */
    public function calculateDuration(): void
    {
        if ($this->started_at && $this->completed_at) {
            $this->duration_seconds = $this->completed_at->diffInSeconds($this->started_at);
            $this->save();
        }
    }

    /**
     * Obtener total de emails procesados
     */
    public function getTotalProcessedAttribute(): int
    {
        return $this->emails_created + $this->emails_updated;
    }
}
