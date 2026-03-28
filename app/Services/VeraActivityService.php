<?php

namespace App\Services;

use App\Models\VeraActivityLog;
use App\Models\VeraTokenUsage;
use App\Models\User;
use Carbon\Carbon;

/**
 * Servicio centralizado para registrar toda la actividad de Vera
 * INTEGRA: Tokens, Planes, Feedback y Analytics
 */
class VeraActivityService
{
    /**
     * Registrar que se alcanzó el límite mensual
     *
     * @param User $user
     * @return VeraActivityLog
     */
    public function logLimitReached(User $user): VeraActivityLog
    {
        return VeraActivityLog::record([
            'user_id' => $user->id,
            'activity_type' => 'limit_reached',
            'title' => "Límite mensual alcanzado",
            'description' => "Has alcanzado el límite de {$user->monthly_meeting_limit} reuniones de tu plan {$user->plan_type}",
            'metadata' => [
                'plan' => $user->plan_type,
                'limit' => $user->monthly_meeting_limit,
            ],
            'tokens_consumed' => 0,
            'cost' => 0,
            'counts_towards_limit' => false,
            'plan_type_at_time' => $user->plan_type,
            'status' => 'completed',
            'user_feedback' => 'pending',
        ]);
    }

    /**
     * Registrar una interacción de chat
     *
     * @param User $user
     * @param string $message
     * @param VeraTokenUsage|null $tokenUsage
     * @return VeraActivityLog
     */
    public function logChatInteraction(User $user, string $message, ?VeraTokenUsage $tokenUsage = null): VeraActivityLog
    {
        return VeraActivityLog::record([
            'user_id' => $user->id,
            'token_usage_id' => $tokenUsage?->id,
            'activity_type' => 'chat_interaction',
            'title' => "Chat con Vera",
            'description' => substr($message, 0, 100),
            'metadata' => [
                'message_preview' => substr($message, 0, 200),
            ],
            'tokens_consumed' => $tokenUsage?->total_tokens ?? 0,
            'cost' => $tokenUsage?->cost ?? 0,
            'counts_towards_limit' => false,  // El chat no cuenta para límite de emails
            'plan_type_at_time' => $user->plan_type,
            'status' => 'completed',
        ]);
    }

    /**
     * Registrar actualización de configuración
     *
     * @param User $user
     * @param string $configKey
     * @param mixed $oldValue
     * @param mixed $newValue
     * @return VeraActivityLog
     */
    public function logConfigUpdated(User $user, string $configKey, $oldValue, $newValue): VeraActivityLog
    {
        return VeraActivityLog::record([
            'user_id' => $user->id,
            'activity_type' => 'config_updated',
            'title' => "Configuración actualizada: {$configKey}",
            'description' => "Cambio de '{$oldValue}' a '{$newValue}'",
            'metadata' => [
                'config_key' => $configKey,
                'old_value' => $oldValue,
                'new_value' => $newValue,
            ],
            'tokens_consumed' => 0,
            'cost' => 0,
            'counts_towards_limit' => false,
            'plan_type_at_time' => $user->plan_type,
            'status' => 'completed',
        ]);
    }

    /**
     * Actualizar feedback de una actividad
     *
     * @param int $activityId
     * @param string $feedback
     * @param string|null $notes
     * @return VeraActivityLog
     */
    public function updateFeedback(int $activityId, string $feedback, ?string $notes = null): VeraActivityLog
    {
        $activity = VeraActivityLog::findOrFail($activityId);

        $activity->update([
            'user_feedback' => $feedback,
            'user_notes' => $notes,
        ]);

        // Si hay una acción asociada, actualizar también su feedback
        if ($activity->veraAction) {
            $activity->veraAction->update([
                'user_feedback' => $feedback,
                'user_notes' => $notes,
            ]);
        }

        return $activity->fresh();
    }

    /**
     * Obtener estadísticas completas de actividad
     *
     * @param User $user
     * @param Carbon|null $startDate
     * @param Carbon|null $endDate
     * @return array
     */
    public function getActivityStats(User $user, ?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        $stats = VeraActivityLog::getStats($user->id, $startDate, $endDate);

        // Agregar comparación con mes anterior
        if (!$startDate && !$endDate) {
            $lastMonthStats = VeraActivityLog::getStats(
                $user->id,
                Carbon::now()->subMonth()->startOfMonth(),
                Carbon::now()->subMonth()->endOfMonth()
            );

            $stats['comparison'] = [
                'activities_change' => $this->calculatePercentageChange(
                    $lastMonthStats['total_activities'],
                    $stats['total_activities']
                ),
                'tokens_change' => $this->calculatePercentageChange(
                    $lastMonthStats['total_tokens'],
                    $stats['total_tokens']
                ),
                'responses_sent_change' => $this->calculatePercentageChange(
                    $lastMonthStats['responses_sent'],
                    $stats['responses_sent']
                ),
            ];
        }

        return $stats;
    }

    /**
     * Calcular cambio porcentual
     *
     * @param float $old
     * @param float $new
     * @return float
     */
    private function calculatePercentageChange(float $old, float $new): float
    {
        if ($old == 0) {
            return $new > 0 ? 100 : 0;
        }

        return round((($new - $old) / $old) * 100, 1);
    }

    /**
     * Obtener actividades recientes con paginación
     *
     * @param User $user
     * @param array $filters
     * @param int $perPage
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getActivitiesPaginated(User $user, array $filters = [], int $perPage = 20)
    {
        return VeraActivityLog::with(['tokenUsage'])
            ->byUser($user->id)
            ->applyFilters($filters)
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }
}
