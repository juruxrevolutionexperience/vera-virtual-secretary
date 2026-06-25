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
        'conversation_id',
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
     * Scope: Solo detección de intención
     */
    public function scopeIntentDetection($query)
    {
        return $query->where('action_type', 'intent_detection');
    }

    /**
     * Scope: Solo generación de respuestas
     */
    public function scopeResponseGeneration($query)
    {
        return $query->where('action_type', 'response_generation');
    }

    /**
     * Scope: Solo propuestas de horario
     */
    public function scopeSlotProposal($query)
    {
        return $query->where('action_type', 'slot_proposal');
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
     * @param string $actionType  intent_detection|response_generation|slot_proposal|chat_interaction|quick_classify
     * @param array $tokenData
     * @param int|null $conversationId
     * @param array $metadata
     * @return self
     */
    public static function record(
        int $userId,
        string $actionType,
        array $tokenData,
        ?int $conversationId = null,
        array $metadata = []
    ): self {
        return self::create([
            'user_id'          => $userId,
            'conversation_id'  => $conversationId,
            'action_type'      => $actionType,
            'prompt_tokens'    => $tokenData['prompt_tokens'] ?? 0,
            'completion_tokens'=> $tokenData['completion_tokens'] ?? 0,
            'total_tokens'     => $tokenData['total_tokens'] ?? 0,
            'model'            => $tokenData['model'] ?? 'gemini-1.5-flash',
            'cost'             => self::calculateCost(
                $tokenData['prompt_tokens']     ?? 0,
                $tokenData['completion_tokens'] ?? 0,
                $tokenData['total_tokens']      ?? 0,
                $tokenData['model']              ?? 'gemini-1.5-flash'
            ),
            'metadata' => $metadata,
        ]);
    }

    /**
     * Precios reales por modelo (verificados 2026-06-25, ver memoria
     * project_coste_real_por_cliente):
     *   - llama-3.3-70b-versatile (Groq, modelo principal): $0.59/M input, $0.79/M output
     *   - gemini-2.5-flash-lite (fallback automático): $0.10/M input, $0.40/M output
     *
     * Antes esta función asumía siempre precio Gemini, aunque la mayoría de
     * llamadas reales van por Groq (más caro), subestimando el coste real.
     */
    public static function calculateCost(
        int $promptTokens = 0,
        int $completionTokens = 0,
        int $totalTokens = 0,
        string $model = 'gemini-1.5-flash'
    ): float {
        $isGroq = str_contains($model, 'llama');
        $inputRate  = $isGroq ? 0.59 : 0.075;
        $outputRate = $isGroq ? 0.79 : 0.300;
        // Promedio ponderado para cuando solo tenemos el total (sin desglose
        // input/output) — asume ~85% input / 15% output, igual que el resto
        // de proyecciones de este modelo.
        $blendedRate = $inputRate * 0.85 + $outputRate * 0.15;

        // Si tenemos desglose input/output, usamos precios diferenciados
        if ($promptTokens > 0 || $completionTokens > 0) {
            $inputCost  = ($promptTokens     / 1_000_000) * $inputRate;
            $outputCost = ($completionTokens / 1_000_000) * $outputRate;
            return round($inputCost + $outputCost, 8);
        }

        // Si solo tenemos total, usamos el precio promedio ponderado del modelo
        return round(($totalTokens / 1_000_000) * $blendedRate, 8);
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
     * Obtener top conversaciones que más tokens consumieron este mes.
     *
     * @param int $userId
     * @param int $limit
     * @return \Illuminate\Support\Collection
     */
    public static function getTopConsumingConversations(int $userId, int $limit = 10)
    {
        return self::byUser($userId)
            ->thisMonth()
            ->whereNotNull('conversation_id')
            ->orderByDesc('total_tokens')
            ->limit($limit)
            ->get();
    }

    /**
     * Detectar anomalías (uso excesivo este mes).
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
            ->with(['user:id,name,email'])
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
     * Basada en reuniones/mes × avg_tokens_por_reunion (conversación completa de agenda).
     *
     * Plan Maestro: Básica 30 reuniones/mes | Pro ilimitado | Equipo ilimitado
     * Precios Gemini Flash (tier de pago):
     *   Input:  $0.075 por 1M tokens
     *   Output: $0.300 por 1M tokens
     */
    public static function getPlanPricingData(int $userId): array
    {
        // Promedio tokens por llamada desde datos reales
        $allData = self::byUser($userId)->get();

        $avgTokensPerCall = $allData->count() > 0
            ? (int) round($allData->avg('total_tokens'))
            : 1500; // Fallback: estimado para una conversación de agenda

        // Estimamos ~4 llamadas IA por reunión confirmada (detección + propuesta + confirmación + respuesta)
        $callsPerMeeting  = 4;
        $avgTokensPerMeeting = $avgTokensPerCall * $callsPerMeeting;

        // Planes según Plan Maestro Vera
        $planDefinitions = [
            'basic' => [
                'name'              => 'Vera Básica',
                'meetings_per_month'=> 30,
                'suggested_price'   => 20,
                'color'             => 'blue',
                'unlimited'         => false,
            ],
            'pro' => [
                'name'              => 'Vera Pro',
                'meetings_per_month'=> 200, // ilimitado, usamos 200 como base de cálculo
                'suggested_price'   => 50,
                'color'             => 'purple',
                'unlimited'         => true,
            ],
            'team' => [
                'name'              => 'Vera Equipo',
                'meetings_per_month'=> 500, // ilimitado, múltiples agendas
                'suggested_price'   => 150,
                'color'             => 'emerald',
                'unlimited'         => true,
            ],
        ];

        foreach ($planDefinitions as &$plan) {
            $tokensPerMonth = $plan['meetings_per_month'] * $avgTokensPerMeeting;
            $inputCost  = ($tokensPerMonth * 0.85 / 1_000_000) * 0.075;
            $outputCost = ($tokensPerMonth * 0.15 / 1_000_000) * 0.300;
            $costUsd    = $inputCost + $outputCost;

            $plan['tokens_per_month'] = $tokensPerMonth;
            $plan['cost_api_usd']     = round($costUsd, 4);
            $plan['cost_api_eur']     = round($costUsd * 0.92, 4);
        }
        unset($plan);

        return [
            'avg_tokens_per_meeting' => $avgTokensPerMeeting,
            'avg_tokens_per_call'    => $avgTokensPerCall,
            'calls_per_meeting'      => $callsPerMeeting,
            'data_points'            => $allData->count(),
            'plans'                  => $planDefinitions,
        ];
    }
}
