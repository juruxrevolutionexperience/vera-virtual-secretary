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
            'cost' => self::calculateCost(
                $tokenData['prompt_tokens']     ?? 0,
                $tokenData['completion_tokens'] ?? 0,
                $tokenData['total_tokens']      ?? 0
            ),
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
    /**
     * Gemini Flash pricing (tier de pago, precios reales Feb 2026):
     *   Input:  $0.075  por 1M tokens
     *   Output: $0.300  por 1M tokens
     *
     * Aunque ahora usemos tier gratuito, calculamos el coste teórico
     * para poder calcular rentabilidad de planes.
     */
    public static function calculateCost(
        int $promptTokens = 0,
        int $completionTokens = 0,
        int $totalTokens = 0
    ): float {
        // Si tenemos desglose input/output, usamos precios diferenciados
        if ($promptTokens > 0 || $completionTokens > 0) {
            $inputCost  = ($promptTokens     / 1_000_000) * 0.075;
            $outputCost = ($completionTokens / 1_000_000) * 0.300;
            return round($inputCost + $outputCost, 8);
        }

        // Si solo tenemos total, usamos precio promedio ponderado (~60/40)
        return round(($totalTokens / 1_000_000) * 0.150, 8);
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

    // ─── Métodos de negocio (para cálculo de planes y rentabilidad) ───────────

    /**
     * Coste real acumulado de un usuario en un período.
     * Recalcula desde los tokens reales (por si los registros antiguos tienen cost=0).
     */
    public static function getRealCostByUser(int $userId, int $year, int $month): array
    {
        $records = self::byUser($userId)->forMonth($year, $month)->get();

        $inputTokens      = $records->sum('prompt_tokens');
        $outputTokens     = $records->sum('completion_tokens');
        $totalTokens      = $records->sum('total_tokens');

        $costUsd = self::calculateCost($inputTokens, $outputTokens, $totalTokens);
        $costEur = $costUsd * 0.92; // cambio aproximado USD → EUR

        return [
            'input_tokens'   => $inputTokens,
            'output_tokens'  => $outputTokens,
            'total_tokens'   => $totalTokens,
            'cost_usd'       => $costUsd,
            'cost_eur'       => $costEur,
            'calls'          => $records->count(),
            'avg_cost_per_call_eur' => $records->count() > 0
                ? round($costEur / $records->count(), 6)
                : 0,
        ];
    }

    /**
     * Proyección de coste mensual con N usuarios del mismo perfil de uso.
     * Útil para calcular el precio mínimo de un plan.
     *
     * Ejemplo: un usuario Basic gasta X tokens/mes → con 100 usuarios = X*100 tokens → Y€
     */
    public static function getCostProjection(int $userId, int $year, int $month): array
    {
        $real = self::getRealCostByUser($userId, $year, $month);

        $projections = [];
        foreach ([10, 50, 100, 500, 1000] as $nUsers) {
            $totalCostEur   = $real['cost_eur'] * $nUsers;
            // Precio mínimo por usuario = coste API / usuarios + 60% margen mínimo
            $minPriceEur    = $nUsers > 0 ? round(($totalCostEur / $nUsers) * 1.6, 2) : 0;

            $projections[] = [
                'users'           => $nUsers,
                'total_cost_eur'  => round($totalCostEur, 2),
                'min_price_eur'   => $minPriceEur,
            ];
        }

        return [
            'base_user_cost_eur' => $real['cost_eur'],
            'base_tokens'        => $real['total_tokens'],
            'projections'        => $projections,
        ];
    }

    /**
     * Datos para la calculadora de pricing por plan.
     * Basada en emails/mes × avg_tokens_per_email (dato real medido).
     *
     * Planes definidos según el modelo de negocio real (monthly_email_limit).
     * Precios Gemini Flash (tier de pago):
     *   Input:  $0.075 por 1M tokens
     *   Output: $0.300 por 1M tokens
     */
    public static function getPlanPricingData(int $userId): array
    {
        // Promedio tokens/email desde datos reales (solo email_analysis)
        $emailAnalysisData = self::byUser($userId)
            ->where('action_type', 'email_analysis')
            ->get();

        $avgTokensPerEmail = $emailAnalysisData->count() > 0
            ? (int) round($emailAnalysisData->avg('total_tokens'))
            : 2067; // Fallback: dato real medido (64.095 tokens / 31 llamadas = 2.067)

        // Definición de planes (emails/mes = parámetro real del negocio)
        // Business = sin límite → usamos 3.000 emails como base de cálculo de coste
        $planDefinitions = [
            'free_trial' => ['name' => 'Free Trial', 'emails_per_month' => 30,   'suggested_price' => 0,  'color' => 'gray',   'unlimited' => false],
            'solo'       => ['name' => 'Solo',       'emails_per_month' => 400,  'suggested_price' => 29, 'color' => 'blue',   'unlimited' => false],
            'pro'        => ['name' => 'Pro',        'emails_per_month' => 1200, 'suggested_price' => 59, 'color' => 'purple', 'unlimited' => false],
            'business'   => ['name' => 'Business',  'emails_per_month' => 3000, 'suggested_price' => 99, 'color' => 'indigo', 'unlimited' => true],
        ];

        foreach ($planDefinitions as &$plan) {
            $tokensPerMonth = $plan['emails_per_month'] * $avgTokensPerEmail;
            // Gemini Flash: ratio real medido ~85% input / 15% output
            $inputCost  = ($tokensPerMonth * 0.85 / 1_000_000) * 0.075;
            $outputCost = ($tokensPerMonth * 0.15 / 1_000_000) * 0.300;
            $costUsd    = $inputCost + $outputCost;

            $plan['tokens_per_month'] = $tokensPerMonth;
            $plan['cost_api_usd']     = round($costUsd, 4);
            $plan['cost_api_eur']     = round($costUsd * 0.92, 4);
        }
        unset($plan);

        // Contexto del límite gratuito de Gemini Flash
        $freeTierDailyLimit  = 1500; // req/día tier gratuito
        $avgEmailsPerUserDay = 100;  // usuario activo típico con 100 emails/día

        return [
            'avg_tokens_per_email' => $avgTokensPerEmail,
            'data_points'          => $emailAnalysisData->count(),
            'plans'                => $planDefinitions,
            'free_tier'            => [
                'daily_limit'    => $freeTierDailyLimit,
                'emails_per_day' => $avgEmailsPerUserDay,
                'max_free_users' => (int) floor($freeTierDailyLimit / $avgEmailsPerUserDay),
            ],
        ];
    }
}
