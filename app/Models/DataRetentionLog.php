<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DataRetentionLog extends Model
{
    protected $fillable = [
        'user_id',
        'data_type',
        'records_deleted',
        'retention_period',
        'trigger',
        'metadata',
        'deleted_at',
        'executed_by',
    ];

    protected $casts = [
        'metadata' => 'array',
        'deleted_at' => 'datetime',
    ];

    // ==========================================
    // RELATIONSHIPS
    // ==========================================

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function executor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'executed_by');
    }

    // ==========================================
    // MÉTODOS ESTÁTICOS
    // ==========================================

    /**
     * Registrar borrado de datos
     */
    public static function record(
        int $userId,
        string $dataType,
        int $recordsDeleted,
        string $retentionPeriod,
        string $trigger = 'automatic',
        ?array $metadata = null,
        ?int $executedBy = null
    ): self {
        return static::create([
            'user_id' => $userId,
            'data_type' => $dataType,
            'records_deleted' => $recordsDeleted,
            'retention_period' => $retentionPeriod,
            'trigger' => $trigger,
            'metadata' => $metadata,
            'deleted_at' => now(),
            'executed_by' => $executedBy,
        ]);
    }
}
