<?php

namespace App\Services;

use App\Models\VeraActivityLog;
use App\Models\VeraTokenUsage;
use App\Models\VeraEmailAction;
use App\Models\Email;
use App\Models\User;
use Carbon\Carbon;

/**
 * Servicio centralizado para registrar toda la actividad de Vera
 * INTEGRA: Tokens, Planes, Feedback y Analytics
 */
class VeraActivityService
{
    /**
     * Registrar que un email fue procesado
     *
     * @param Email $email
     * @param VeraTokenUsage|null $tokenUsage
     * @param array $metadata
     * @return VeraActivityLog
     */
    public function logEmailProcessed(Email $email, ?VeraTokenUsage $tokenUsage = null, array $metadata = []): VeraActivityLog
    {
        $user = $email->user;

        return VeraActivityLog::record([
            'user_id' => $user->id,
            'email_id' => $email->id,
            'token_usage_id' => $tokenUsage?->id,
            'activity_type' => 'email_processed',
            'title' => "Email procesado de {$email->from_email}",
            'description' => "Asunto: {$email->subject}",
            'metadata' => array_merge([
                'from' => $email->from_email,
                'subject' => $email->subject,
                'category' => $email->category?->name,
                'priority' => $email->priority,
            ], $metadata),
            'tokens_consumed' => $tokenUsage?->total_tokens ?? 0,
            'cost' => $tokenUsage?->cost ?? 0,
            'counts_towards_limit' => true,
            'plan_type_at_time' => $user->plan_type,
            'status' => 'completed',
            'confidence_score' => $metadata['confidence_score'] ?? null,
            'user_feedback' => 'pending',
        ]);
    }

    /**
     * Registrar que se generó/envió una respuesta
     *
     * @param Email $email
     * @param VeraEmailAction $action
     * @param VeraTokenUsage|null $tokenUsage
     * @param bool $wasSent
     * @return VeraActivityLog
     */
    public function logEmailReplied(
        Email $email,
        VeraEmailAction $action,
        ?VeraTokenUsage $tokenUsage = null,
        bool $wasSent = false
    ): VeraActivityLog {
        $user = $email->user;

        $title = $wasSent
            ? "Respuesta enviada a {$email->from_email}"
            : "Respuesta generada para {$email->from_email}";

        return VeraActivityLog::record([
            'user_id' => $user->id,
            'email_id' => $email->id,
            'vera_action_id' => $action->id,
            'token_usage_id' => $tokenUsage?->id,
            'activity_type' => 'email_replied',
            'title' => $title,
            'description' => $action->reasoning ?? "Asunto: {$email->subject}",
            'metadata' => [
                'from' => $email->from_email,
                'subject' => $email->subject,
                'response_sent' => $wasSent,
                'response_preview' => substr($action->generated_response ?? '', 0, 100),
            ],
            'tokens_consumed' => $tokenUsage?->total_tokens ?? 0,
            'cost' => $tokenUsage?->cost ?? 0,
            'counts_towards_limit' => true,
            'plan_type_at_time' => $user->plan_type,
            'status' => 'completed',
            'confidence_score' => $action->confidence_score,
            'user_feedback' => $action->user_feedback ?? 'pending',
        ]);
    }

    /**
     * Registrar que un email fue archivado
     *
     * @param Email $email
     * @param string|null $reason
     * @return VeraActivityLog
     */
    public function logEmailArchived(Email $email, ?string $reason = null): VeraActivityLog
    {
        $user = $email->user;

        return VeraActivityLog::record([
            'user_id' => $user->id,
            'email_id' => $email->id,
            'activity_type' => 'email_archived',
            'title' => "Email archivado de {$email->from_email}",
            'description' => $reason ?? "Asunto: {$email->subject}",
            'metadata' => [
                'from' => $email->from_email,
                'subject' => $email->subject,
                'reason' => $reason,
            ],
            'tokens_consumed' => 0,  // Archivar no consume tokens extra
            'cost' => 0,
            'counts_towards_limit' => false,  // No cuenta para límite
            'plan_type_at_time' => $user->plan_type,
            'status' => 'completed',
            'user_feedback' => 'pending',
        ]);
    }

    /**
     * Registrar que un email fue categorizado
     *
     * @param Email $email
     * @param string $category
     * @param float|null $confidence
     * @return VeraActivityLog
     */
    public function logEmailCategorized(Email $email, string $category, ?float $confidence = null): VeraActivityLog
    {
        $user = $email->user;

        return VeraActivityLog::record([
            'user_id' => $user->id,
            'email_id' => $email->id,
            'activity_type' => 'email_categorized',
            'title' => "Email categorizado como '{$category}'",
            'description' => "De {$email->from_email}: {$email->subject}",
            'metadata' => [
                'from' => $email->from_email,
                'subject' => $email->subject,
                'category' => $category,
            ],
            'tokens_consumed' => 0,  // La categorización ya se contó en email_processed
            'cost' => 0,
            'counts_towards_limit' => false,
            'plan_type_at_time' => $user->plan_type,
            'status' => 'completed',
            'confidence_score' => $confidence,
            'user_feedback' => 'pending',
        ]);
    }

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
            'description' => "Has alcanzado el límite de {$user->monthly_email_limit} emails de tu plan {$user->plan_type}",
            'metadata' => [
                'plan' => $user->plan_type,
                'limit' => $user->monthly_email_limit,
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
                'emails_replied_change' => $this->calculatePercentageChange(
                    $lastMonthStats['emails_replied'],
                    $stats['emails_replied']
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
        return VeraActivityLog::with(['email', 'veraAction', 'tokenUsage'])
            ->byUser($user->id)
            ->applyFilters($filters)
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }
}
