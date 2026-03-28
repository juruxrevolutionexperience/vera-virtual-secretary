<?php

namespace App\Services;

use App\Models\VeraActivityLog;
use App\Models\VeraTokenUsage;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Servicio de Analytics para Vera
 * FASE 2: Métricas, Gráficos, Tendencias y Predicciones
 */
class VeraAnalyticsService
{
    /**
     * Obtener datos de actividad para gráfico de tendencias (últimos 30 días)
     *
     * @param User $user
     * @param int $days
     * @return array
     */
    public function getActivityTrend(User $user, int $days = 30): array
    {
        $startDate = Carbon::now()->subDays($days)->startOfDay();

        $activities = VeraActivityLog::byUser($user->id)
            ->where('created_at', '>=', $startDate)
            ->select([
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as total'),
                DB::raw('SUM(tokens_consumed) as tokens'),
                DB::raw('SUM(cost) as cost'),
            ])
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Llenar días sin datos con ceros
        $dateRange = [];
        $data = [];

        for ($i = 0; $i < $days; $i++) {
            $date = Carbon::now()->subDays($days - $i - 1)->format('Y-m-d');
            $dateRange[] = $date;

            $dayActivity = $activities->firstWhere('date', $date);

            $data[] = [
                'date' => $date,
                'date_formatted' => Carbon::parse($date)->format('d M'),
                'total_activities' => $dayActivity?->total ?? 0,
                'tokens' => $dayActivity?->tokens ?? 0,
                'cost' => $dayActivity?->cost ?? 0,
            ];
        }

        return $data;
    }

    /**
     * Obtener distribución de actividades por tipo
     *
     * @param User $user
     * @param Carbon|null $startDate
     * @param Carbon|null $endDate
     * @return array
     */
    public function getActivityDistribution(User $user, ?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        $query = VeraActivityLog::byUser($user->id);

        if ($startDate && $endDate) {
            $query->betweenDates($startDate, $endDate);
        } else {
            $query->thisMonth();
        }

        $distribution = $query->select([
                'activity_type',
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(tokens_consumed) as tokens'),
                DB::raw('SUM(cost) as cost'),
            ])
            ->groupBy('activity_type')
            ->orderByDesc('count')
            ->get()
            ->map(function ($item) {
                return [
                    'type' => $item->activity_type,
                    'type_label' => $this->getActivityTypeLabel($item->activity_type),
                    'count' => $item->count,
                    'tokens' => $item->tokens,
                    'cost' => $item->cost,
                    'percentage' => 0, // Se calculará después
                ];
            });

        // Calcular porcentajes
        $total = $distribution->sum('count');

        if ($total > 0) {
            $distribution = $distribution->map(function ($item) use ($total) {
                $item['percentage'] = round(($item['count'] / $total) * 100, 1);
                return $item;
            });
        }

        return $distribution->toArray();
    }

    /**
     * Obtener comparativa entre dos períodos (mes actual vs mes anterior)
     *
     * @param User $user
     * @return array
     */
    public function getMonthComparison(User $user): array
    {
        $currentMonth = VeraActivityLog::byUser($user->id)
            ->whereYear('created_at', Carbon::now()->year)
            ->whereMonth('created_at', Carbon::now()->month)
            ->select([
                DB::raw('COUNT(*) as total_activities'),
                DB::raw('SUM(tokens_consumed) as total_tokens'),
                DB::raw('SUM(cost) as total_cost'),
                DB::raw('COUNT(CASE WHEN activity_type = "meeting_confirmed" THEN 1 END) as meetings_confirmed'),
                DB::raw('AVG(CASE WHEN confidence_score IS NOT NULL THEN confidence_score END) as avg_confidence'),
            ])
            ->first();

        $lastMonth = VeraActivityLog::byUser($user->id)
            ->whereYear('created_at', Carbon::now()->subMonth()->year)
            ->whereMonth('created_at', Carbon::now()->subMonth()->month)
            ->select([
                DB::raw('COUNT(*) as total_activities'),
                DB::raw('SUM(tokens_consumed) as total_tokens'),
                DB::raw('SUM(cost) as total_cost'),
                DB::raw('COUNT(CASE WHEN activity_type = "meeting_confirmed" THEN 1 END) as meetings_confirmed'),
                DB::raw('AVG(CASE WHEN confidence_score IS NOT NULL THEN confidence_score END) as avg_confidence'),
            ])
            ->first();

        return [
            'current_month' => [
                'activities'         => $currentMonth->total_activities ?? 0,
                'tokens'             => $currentMonth->total_tokens ?? 0,
                'cost'               => $currentMonth->total_cost ?? 0,
                'meetings_confirmed' => $currentMonth->meetings_confirmed ?? 0,
                'avg_confidence'     => $currentMonth->avg_confidence ?? 0,
            ],
            'last_month' => [
                'activities'         => $lastMonth->total_activities ?? 0,
                'tokens'             => $lastMonth->total_tokens ?? 0,
                'cost'               => $lastMonth->total_cost ?? 0,
                'meetings_confirmed' => $lastMonth->meetings_confirmed ?? 0,
                'avg_confidence'     => $lastMonth->avg_confidence ?? 0,
            ],
            'change' => [
                'activities'         => $this->calculateChange($lastMonth->total_activities ?? 0, $currentMonth->total_activities ?? 0),
                'tokens'             => $this->calculateChange($lastMonth->total_tokens ?? 0, $currentMonth->total_tokens ?? 0),
                'cost'               => $this->calculateChange($lastMonth->total_cost ?? 0, $currentMonth->total_cost ?? 0),
                'meetings_confirmed' => $this->calculateChange($lastMonth->meetings_confirmed ?? 0, $currentMonth->meetings_confirmed ?? 0),
            ]
        ];
    }

    /**
     * Obtener análisis de costos por día de la semana
     *
     * @param User $user
     * @param int $weeks
     * @return array
     */
    public function getCostByDayOfWeek(User $user, int $weeks = 4): array
    {
        $startDate = Carbon::now()->subWeeks($weeks)->startOfDay();

        $data = VeraActivityLog::byUser($user->id)
            ->where('created_at', '>=', $startDate)
            ->select([
                DB::raw('DAYOFWEEK(created_at) as day_of_week'),
                DB::raw('COUNT(*) as total'),
                DB::raw('SUM(cost) as cost'),
                DB::raw('AVG(cost) as avg_cost'),
            ])
            ->groupBy('day_of_week')
            ->orderBy('day_of_week')
            ->get();

        // Convertir a días de la semana en español
        $daysMap = [
            1 => 'Domingo',
            2 => 'Lunes',
            3 => 'Martes',
            4 => 'Miércoles',
            5 => 'Jueves',
            6 => 'Viernes',
            7 => 'Sábado',
        ];

        $result = [];

        for ($i = 2; $i <= 7; $i++) { // Empezar en lunes (2)
            $dayData = $data->firstWhere('day_of_week', $i);
            $result[] = [
                'day' => $daysMap[$i],
                'total' => $dayData?->total ?? 0,
                'cost' => $dayData?->cost ?? 0,
                'avg_cost' => $dayData?->avg_cost ?? 0,
            ];
        }

        // Agregar domingo al final
        $dayData = $data->firstWhere('day_of_week', 1);
        $result[] = [
            'day' => $daysMap[1],
            'total' => $dayData?->total ?? 0,
            'cost' => $dayData?->cost ?? 0,
            'avg_cost' => $dayData?->avg_cost ?? 0,
        ];

        return $result;
    }

    // getTopEmailCategories removed — no email model in new Vera (Sprint 3: replace with meeting types)

    /**
     * Predecir consumo mensual basado en tendencia actual
     *
     * @param User $user
     * @return array
     */
    public function predictMonthlyUsage(User $user): array
    {
        $daysInMonth = Carbon::now()->daysInMonth;
        $currentDay = Carbon::now()->day;

        $monthUsage = VeraActivityLog::byUser($user->id)
            ->thisMonth()
            ->select([
                DB::raw('COUNT(*) as activities'),
                DB::raw('SUM(tokens_consumed) as tokens'),
                DB::raw('SUM(cost) as cost'),
                DB::raw('COUNT(CASE WHEN counts_towards_limit = 1 THEN 1 END) as meetings_count'),
            ])
            ->first();

        $activities = $monthUsage->activities ?? 0;
        $tokens = $monthUsage->tokens ?? 0;
        $cost = $monthUsage->cost ?? 0;
        $meetingsCount = $monthUsage->meetings_count ?? 0;

        if ($currentDay == 0) {
            $currentDay = 1;
        }

        $avgActivitiesPerDay  = $activities / $currentDay;
        $avgTokensPerDay      = $tokens / $currentDay;
        $avgCostPerDay        = $cost / $currentDay;
        $avgMeetingsPerDay    = $meetingsCount / $currentDay;
        $daysRemaining        = $daysInMonth - $currentDay;
        $meetingLimit         = $user->monthly_meeting_limit ?? 999999;

        return [
            'current' => [
                'activities' => $activities,
                'tokens'     => $tokens,
                'cost'       => $cost,
                'meetings'   => $meetingsCount,
            ],
            'daily_average' => [
                'activities' => round($avgActivitiesPerDay, 1),
                'tokens'     => round($avgTokensPerDay),
                'cost'       => round($avgCostPerDay, 4),
                'meetings'   => round($avgMeetingsPerDay, 1),
            ],
            'projected_end_of_month' => [
                'activities' => round($avgActivitiesPerDay * $daysInMonth),
                'tokens'     => round($avgTokensPerDay * $daysInMonth),
                'cost'       => round($avgCostPerDay * $daysInMonth, 2),
                'meetings'   => round($avgMeetingsPerDay * $daysInMonth),
            ],
            'remaining_days'       => $daysRemaining,
            'completion_percentage'=> round(($currentDay / $daysInMonth) * 100, 1),
            'limit'                => $meetingLimit,
            'will_exceed_limit'    => ($avgMeetingsPerDay * $daysInMonth) > $meetingLimit,
        ];
    }

    /**
     * Obtener análisis de feedback del usuario
     *
     * @param User $user
     * @return array
     */
    public function getFeedbackAnalysis(User $user): array
    {
        $feedback = VeraActivityLog::byUser($user->id)
            ->select([
                'user_feedback',
                DB::raw('COUNT(*) as count'),
                DB::raw('AVG(confidence_score) as avg_confidence'),
            ])
            ->groupBy('user_feedback')
            ->get();

        $total = $feedback->sum('count');

        $feedbackMap = [
            'approved' => 'Aprobado',
            'rejected' => 'Rechazado',
            'modified' => 'Modificado',
            'pending' => 'Pendiente',
        ];

        $result = [];

        foreach ($feedbackMap as $key => $label) {
            $item = $feedback->firstWhere('user_feedback', $key);
            $count = $item?->count ?? 0;

            $result[] = [
                'feedback' => $key,
                'label' => $label,
                'count' => $count,
                'percentage' => $total > 0 ? round(($count / $total) * 100, 1) : 0,
                'avg_confidence' => $item?->avg_confidence ? round($item->avg_confidence * 100, 1) : 0,
            ];
        }

        return $result;
    }

    /**
     * Obtener resumen ejecutivo completo
     *
     * @param User $user
     * @return array
     */
    public function getExecutiveSummary(User $user): array
    {
        return [
            'trends' => $this->getActivityTrend($user, 30),
            'distribution' => $this->getActivityDistribution($user),
            'comparison' => $this->getMonthComparison($user),
            'cost_by_day' => $this->getCostByDayOfWeek($user),
            'top_categories' => [],
            'prediction' => $this->predictMonthlyUsage($user),
            'feedback' => $this->getFeedbackAnalysis($user),
        ];
    }

    /**
     * Helpers
     */

    private function calculateChange($old, $new): array
    {
        if ($old == 0) {
            $percentage = $new > 0 ? 100 : 0;
        } else {
            $percentage = round((($new - $old) / $old) * 100, 1);
        }

        return [
            'value' => $new - $old,
            'percentage' => $percentage,
            'direction' => $percentage > 0 ? 'up' : ($percentage < 0 ? 'down' : 'neutral'),
        ];
    }

    private function getActivityTypeLabel(string $type): string
    {
        return match($type) {
            'meeting_confirmed'   => 'Reunión confirmada',
            'meeting_requested'   => 'Solicitud recibida',
            'meeting_rescheduled' => 'Reunión reprogramada',
            'meeting_cancelled'   => 'Reunión cancelada',
            'response_sent'       => 'Respuesta enviada',
            'escalation'          => 'Escalado a humano',
            'chat_interaction'    => 'Chat con Vera',
            'limit_reached'       => 'Límite alcanzado',
            default               => ucfirst(str_replace('_', ' ', $type)),
        };
    }
}
