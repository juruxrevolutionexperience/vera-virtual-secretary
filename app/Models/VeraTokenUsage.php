<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class VeraTokenUsage extends Model
{
    use HasFactory;

    protected $table = 'vera_token_usage';

    protected $fillable = [
        'user_id',
        'email_id',
        'action_type',
        'prompt_tokens',
        'completion_tokens',
        'total_tokens',
        'model',
        'cost',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'cost' => 'decimal:6',
        'prompt_tokens' => 'integer',
        'completion_tokens' => 'integer',
        'total_tokens' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relación con User
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relación con Email
     */
    public function email(): BelongsTo
    {
        return $this->belongsTo(Email::class);
    }

    /**
     * Scope: Registros del mes actual
     */
    public function scopeThisMonth($query)
    {
        return $query->whereYear('created_at', Carbon::now()->year)
                    ->whereMonth('created_at', Carbon::now()->month);
    }

    /**
     * Scope: Registros de un mes específico
     */
    public function scopeForMonth($query, int $year, int $month)
    {
        return $query->whereYear('created_at', $year)
                    ->whereMonth('created_at', $month);
    }

    /**
     * Scope: Por tipo de acción
     */
    public function scopeByActionType($query, string $actionType)
    {
        return $query->where('action_type', $actionType);
    }

    /**
     * Scope: Por usuario
     */
    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope: Entre fechas
     */
    public function scopeBetweenDates($query, Carbon $startDate, Carbon $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    /**
     * Scope: Solo análisis de emails
     */
    public function scopeEmailAnalysis($query)
    {
        return $query->where('action_type', 'email_analysis');
    }

    /**
     * Scope: Solo generación de respuestas
     */
    public function scopeResponseGeneration($query)
    {
        return $query->where('action_type', 'response_generation');
    }

    /**
     * Scope: Solo chat
     */
    public function scopeChatInteraction($query)
    {
        return $query->where('action_type', 'chat_interaction');
    }

    /**
     * Registrar uso de tokens
     *
     * @param int $userId
     * @param string $actionType
     * @param array $tokenData
     * @param int|null $emailId
     * @param array $metadata
     * @return self
     */
    public static function record(
        int $userId,
        string $actionType,
        array $tokenData,
        ?int $emailId = null,
        array $metadata = []
    ): self {
        return self::create([
            'user_id' => $userId,
            'email_id' => $emailId,
            'action_type' => $actionType,
            'prompt_tokens' => $tokenData['prompt_tokens'] ?? 0,
            'completion_tokens' => $tokenData['completion_tokens'] ?? 0,
            'total_tokens' => $tokenData['total_tokens'] ?? 0,
            'model' => $tokenData['model'] ?? 'gemini-1.5-flash',
            'cost' => self::calculateCost($tokenData['total_tokens'] ?? 0),
            'metadata' => $metadata,
        ]);
    }

    /**
     * Calcular costo basado en tokens
     * Gemini 1.5 Flash pricing (tier de pago):
     * - Input: $0.075 per 1M tokens
     * - Output: $0.30 per 1M tokens
     * - Promedio: ~$0.10 per 1M tokens
     *
     * @param int $totalTokens
     * @return float
     */
    public static function calculateCost(int $totalTokens): float
    {
        // Por ahora usamos tier gratuito, costo = 0
        // Cuando migremos a tier de pago, descomentar:
        // $costPerMillionTokens = 0.10;
        // return ($totalTokens / 1000000) * $costPerMillionTokens;

        return 0.0;
    }

    /**
     * Obtener estadísticas de uso para un usuario
     *
     * @param int $userId
     * @param int|null $year
     * @param int|null $month
     * @return array
     */
    public static function getStatsByUser(int $userId, ?int $year = null, ?int $month = null): array
    {
        $query = self::byUser($userId);

        if ($year && $month) {
            $query->forMonth($year, $month);
        } else {
            $query->thisMonth();
        }

        $records = $query->get();

        $byAction = $records->groupBy('action_type')->map(function ($group) {
            return [
                'count' => $group->count(),
                'total_tokens' => $group->sum('total_tokens'),
                'avg_tokens' => round($group->avg('total_tokens'), 0),
                'cost' => $group->sum('cost'),
            ];
        })->toArray();

        return [
            'total_actions' => $records->count(),
            'total_tokens' => $records->sum('total_tokens'),
            'total_cost' => $records->sum('cost'),
            'avg_tokens_per_action' => $records->count() > 0
                ? round($records->avg('total_tokens'), 0)
                : 0,
            'by_action_type' => $byAction,
            'period' => [
                'year' => $year ?? Carbon::now()->year,
                'month' => $month ?? Carbon::now()->month,
            ],
        ];
    }

    /**
     * Obtener top emails que más tokens consumieron
     *
     * @param int $userId
     * @param int $limit
     * @return \Illuminate\Support\Collection
     */
    public static function getTopConsumingEmails(int $userId, int $limit = 10)
    {
        return self::byUser($userId)
            ->thisMonth()
            ->whereNotNull('email_id')
            ->with('email:id,subject,from_email')
            ->orderByDesc('total_tokens')
            ->limit($limit)
            ->get();
    }

    /**
     * Detectar anomalías (uso excesivo)
     *
     * @param float $threshold Multiplicador del promedio (ej: 3.0 = 3x más que promedio)
     * @return \Illuminate\Support\Collection
     */
    public static function detectAnomalies(float $threshold = 3.0)
    {
        $avgTokens = self::thisMonth()->avg('total_tokens') ?? 0;

        if ($avgTokens === 0) {
            return collect();
        }

        return self::thisMonth()
            ->where('total_tokens', '>', $avgTokens * $threshold)
            ->with(['user:id,name,email', 'email:id,subject,from_email'])
            ->orderByDesc('total_tokens')
            ->get();
    }
}
