<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class VeraActivityLog extends Model
{
    use HasFactory;

    protected $table = 'vera_activity_log';

    protected $fillable = [
        'user_id',
        'conversation_id',
        'meeting_id',
        'token_usage_id',
        'activity_type',
        'title',
        'description',
        'metadata',
        'tokens_consumed',
        'cost',
        'counts_towards_limit',
        'plan_type_at_time',
        'status',
        'confidence_score',
        'user_feedback',
        'user_notes',
    ];

    protected $casts = [
        'metadata' => 'array',
        'tokens_consumed' => 'integer',
        'cost' => 'decimal:6',
        'counts_towards_limit' => 'boolean',
        'confidence_score' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relaciones
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function tokenUsage(): BelongsTo
    {
        return $this->belongsTo(VeraTokenUsage::class, 'token_usage_id');
    }

    /**
     * Scopes
     */
    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByType($query, string $type)
    {
        // "Respuesta enviada" engloba TODO lo que Vera contesta al cliente:
        // respuestas generales, propuestas de slots, confirmaciones y escalados.
        if ($type === 'response_sent') {
            return $query->whereIn('activity_type', [
                'response_sent',
                'slot_proposed',
                'meeting_confirmed',
                'escalation',
            ]);
        }

        return $query->where('activity_type', $type);
    }

    public function scopeByFeedback($query, string $feedback)
    {
        return $query->where('user_feedback', $feedback);
    }

    public function scopeThisMonth($query)
    {
        return $query->whereYear('created_at', Carbon::now()->year)
                    ->whereMonth('created_at', Carbon::now()->month);
    }

    public function scopeBetweenDates($query, Carbon $startDate, Carbon $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    public function scopeHighConfidence($query)
    {
        return $query->where('confidence_score', '>=', 0.80);
    }

    public function scopeMediumConfidence($query)
    {
        return $query->whereBetween('confidence_score', [0.60, 0.79]);
    }

    public function scopeLowConfidence($query)
    {
        return $query->where('confidence_score', '<', 0.60);
    }

    public function scopeCountsTowardsLimit($query)
    {
        return $query->where('counts_towards_limit', true);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopePending($query)
    {
        return $query->where('user_feedback', 'pending');
    }

    /**
     * Scope: Aplicar filtros dinámicamente
     */
    public function scopeApplyFilters($query, array $filters)
    {
        // Filtro por tipo
        if (!empty($filters['type'])) {
            $query->byType($filters['type']);
        }

        // Filtro por feedback
        if (!empty($filters['feedback'])) {
            $query->byFeedback($filters['feedback']);
        }

        // Filtro por rango de fechas
        if (!empty($filters['date_from']) && !empty($filters['date_to'])) {
            $query->betweenDates(
                Carbon::parse($filters['date_from']),
                Carbon::parse($filters['date_to'])
            );
        }

        // Filtro por nivel de confianza
        if (!empty($filters['confidence'])) {
            switch ($filters['confidence']) {
                case 'high':
                    $query->highConfidence();
                    break;
                case 'medium':
                    $query->mediumConfidence();
                    break;
                case 'low':
                    $query->lowConfidence();
                    break;
            }
        }

        return $query;
    }

    /**
     * Métodos helper
     */

    /**
     * Registrar una nueva actividad
     *
     * @param array $data
     * @return self
     */
    public static function record(array $data): self
    {
        // Asegurar que tenemos user_id
        if (!isset($data['user_id']) && auth()->check()) {
            $data['user_id'] = auth()->id();
        }

        // Asegurar que tenemos plan_type_at_time
        if (!isset($data['plan_type_at_time']) && isset($data['user_id'])) {
            $user = User::find($data['user_id']);
            $data['plan_type_at_time'] = $user?->plan_type ?? 'basic';
        }

        // Saneado UTF-8: el texto de emails entrantes puede traer bytes inválidos
        // (tildes cortadas, codificación latin1...) que reventarían el INSERT en MySQL
        // y tumbarían el job. Eliminamos secuencias inválidas para que NUNCA falle.
        foreach (['title', 'description'] as $field) {
            if (isset($data[$field]) && is_string($data[$field])) {
                $data[$field] = (string) @iconv('UTF-8', 'UTF-8//IGNORE', $data[$field]);
            }
        }

        return self::create($data);
    }

    /**
     * Obtener estadísticas del período
     *
     * @param int $userId
     * @param Carbon|null $startDate
     * @param Carbon|null $endDate
     * @return array
     */
    public static function getStats(int $userId, ?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        $query = self::byUser($userId);

        if ($startDate && $endDate) {
            $query->betweenDates($startDate, $endDate);
        } else {
            $query->thisMonth();
        }

        $activities = $query->get();

        return [
            'total_activities' => $activities->count(),
            'total_tokens' => $activities->sum('tokens_consumed'),
            'total_cost' => $activities->sum('cost'),
            'avg_confidence' => $activities->where('confidence_score', '>', 0)->avg('confidence_score') ?? 0,
            'by_type' => $activities->groupBy('activity_type')->map->count()->toArray(),
            'by_feedback' => $activities->groupBy('user_feedback')->map->count()->toArray(),
            'by_status' => $activities->groupBy('status')->map->count()->toArray(),
            'meetings_confirmed' => $activities->where('activity_type', 'meeting_confirmed')->count(),
            'responses_sent' => $activities->whereIn('activity_type', ['response_sent', 'slot_proposed', 'meeting_confirmed', 'escalation'])->count(),
            'approval_rate' => $activities->where('user_feedback', '!=', 'pending')->count() > 0
                ? round(($activities->where('user_feedback', 'approved')->count() / $activities->where('user_feedback', '!=', 'pending')->count()) * 100, 1)
                : 0,
        ];
    }

    /**
     * Obtener actividades recientes
     *
     * @param int $userId
     * @param int $limit
     * @return \Illuminate\Support\Collection
     */
    public static function getRecent(int $userId, int $limit = 10)
    {
        return self::byUser($userId)
            ->with(['email:id,subject,from_email', 'veraAction', 'tokenUsage'])
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();
    }

    /**
     * Obtener actividades pendientes de feedback
     *
     * @param int $userId
     * @return \Illuminate\Support\Collection
     */
    public static function getPendingFeedback(int $userId)
    {
        return self::byUser($userId)
            ->pending()
            ->with(['email:id,subject,from_email', 'veraAction'])
            ->orderByDesc('created_at')
            ->get();
    }

    /**
     * Accessors
     */
    public function getConfidenceLevelAttribute(): string
    {
        if (!$this->confidence_score) {
            return 'unknown';
        }

        if ($this->confidence_score >= 0.80) {
            return 'high';
        } elseif ($this->confidence_score >= 0.60) {
            return 'medium';
        } else {
            return 'low';
        }
    }

    public function getConfidenceBadgeColorAttribute(): string
    {
        return match($this->confidence_level) {
            'high' => 'green',
            'medium' => 'yellow',
            'low' => 'red',
            default => 'gray',
        };
    }

    public function getFeedbackBadgeColorAttribute(): string
    {
        return match($this->user_feedback) {
            'approved' => 'green',
            'rejected' => 'red',
            'modified' => 'yellow',
            'pending' => 'gray',
            default => 'gray',
        };
    }

    public function getActivityIconAttribute(): string
    {
        return match($this->activity_type) {
            'email_processed' => '📧',
            'email_replied' => '↩️',
            'email_archived' => '📁',
            'email_categorized' => '🏷️',
            'chat_interaction' => '💬',
            'config_updated' => '⚙️',
            'rule_created' => '📝',
            'training_updated' => '🎓',
            'limit_reached' => '⚠️',
            default => '•',
        };
    }
}
