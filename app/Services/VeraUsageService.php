<?php

namespace App\Services;

use App\Models\User;
use App\Models\Email;
use App\Models\VeraTokenUsage;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * VeraUsageService
 *
 * Servicio para gestionar límites de uso, cálculo de emails procesados
 * y control de tokens consumidos por Vera
 */
class VeraUsageService
{
    /**
     * Promedio de tokens por email procesado
     * Calculado en base a: 60% análisis (500 tokens) + 40% respuestas (2000 tokens)
     */
    const TOKENS_PER_EMAIL = 1100;

    /**
     * Calcular uso mensual completo del usuario
     *
     * @param User $user
     * @return array
     */
    public function calculateMonthlyUsage(User $user): array
    {
        $startDate = $this->getBillingCycleStart($user);
        $endDate = $this->getBillingCycleEnd($user);

        // Obtener todos los registros del ciclo actual
        $records = VeraTokenUsage::where('user_id', $user->id)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->get();

        // Agrupar por tipo de acción
        $byAction = $records->groupBy('action_type')->map(function ($group) {
            return [
                'count' => $group->count(),
                'total_tokens' => $group->sum('total_tokens'),
                'avg_tokens' => $group->count() > 0 ? round($group->avg('total_tokens'), 0) : 0,
                'cost' => $group->sum('cost'),
            ];
        })->toArray();

        // Calcular emails procesados (emails únicos que consumieron tokens)
        $emailsProcessed = $this->getEmailsProcessedThisMonth($user);

        return [
            'user_id' => $user->id,
            'plan_type' => $user->plan_type,
            'billing_cycle_start' => $startDate->toDateString(),
            'billing_cycle_end' => $endDate->toDateString(),
            'days_in_cycle' => $startDate->diffInDays($endDate),
            'days_elapsed' => $startDate->diffInDays(now()),
            'days_remaining' => now()->diffInDays($endDate),

            // Límites del plan
            'email_limit' => $user->monthly_email_limit,
            'token_limit' => $this->emailsToTokens($user->monthly_email_limit),

            // Uso actual
            'emails_processed' => $emailsProcessed,
            'emails_remaining' => max(0, $user->monthly_email_limit - $emailsProcessed),
            'total_tokens' => $records->sum('total_tokens'),
            'total_cost' => $records->sum('cost'),

            // Porcentajes
            'usage_percentage' => $this->getUsagePercentage($user),

            // Desglose por acción
            'by_action_type' => $byAction,

            // Proyección
            'projected_limit_date' => $this->projectLimitReachDate($user),
            'avg_emails_per_week' => $this->getAverageEmailsPerWeek($user),
        ];
    }

    /**
     * Obtener cantidad de emails procesados este mes
     * Un "email procesado" = un email único que consumió tokens de IA
     *
     * @param User $user
     * @return int
     */
    public function getEmailsProcessedThisMonth(User $user): int
    {
        $startDate = $this->getBillingCycleStart($user);
        $endDate = $this->getBillingCycleEnd($user);

        // Contar emails únicos que fueron procesados este ciclo
        return Email::where('user_id', $user->id)
            ->where('processed_this_billing_cycle', true)
            ->whereBetween('last_processed_at', [$startDate, $endDate])
            ->count();
    }

    /**
     * Obtener emails restantes en el plan
     *
     * @param User $user
     * @return int
     */
    public function getRemainingEmails(User $user): int
    {
        $used = $this->getEmailsProcessedThisMonth($user);
        return max(0, $user->monthly_email_limit - $used);
    }

    /**
     * Obtener tokens consumidos por tipo de acción
     *
     * @param User $user
     * @param int|null $year
     * @param int|null $month
     * @return array
     */
    public function getTokensByActionType(User $user, ?int $year = null, ?int $month = null): array
    {
        $query = VeraTokenUsage::where('user_id', $user->id);

        if ($year && $month) {
            $query->whereYear('created_at', $year)
                  ->whereMonth('created_at', $month);
        } else {
            $startDate = $this->getBillingCycleStart($user);
            $endDate = $this->getBillingCycleEnd($user);
            $query->whereBetween('created_at', [$startDate, $endDate]);
        }

        return $query->selectRaw('
                action_type,
                COUNT(*) as count,
                SUM(total_tokens) as total_tokens,
                AVG(total_tokens) as avg_tokens,
                SUM(cost) as total_cost
            ')
            ->groupBy('action_type')
            ->get()
            ->keyBy('action_type')
            ->toArray();
    }

    /**
     * Verificar si el usuario puede procesar más emails
     *
     * @param User $user
     * @return bool
     */
    public function canProcessEmail(User $user): bool
    {
        $remaining = $this->getRemainingEmails($user);

        Log::info('🔍 Verificando límite de emails', [
            'user_id' => $user->id,
            'plan' => $user->plan_type,
            'limit' => $user->monthly_email_limit,
            'used' => $this->getEmailsProcessedThisMonth($user),
            'remaining' => $remaining,
            'can_process' => $remaining > 0,
        ]);

        return $remaining > 0;
    }

    /**
     * Registrar procesamiento de email
     * Marca el email como procesado en este ciclo de facturación
     *
     * @param User $user
     * @param Email $email
     * @param string $actionType
     * @return void
     */
    public function recordEmailProcessing(User $user, Email $email, string $actionType): void
    {
        // Solo marcar si no está ya marcado en este ciclo
        if ($email->processed_this_billing_cycle) {
            Log::info('ℹ️  Email ya procesado este ciclo, no se cuenta de nuevo', [
                'email_id' => $email->id,
                'user_id' => $user->id,
            ]);
            return;
        }

        // Marcar email como procesado
        $email->update([
            'processed_this_billing_cycle' => true,
            'last_processed_at' => now(),
        ]);

        Log::info('✅ Email marcado como procesado', [
            'email_id' => $email->id,
            'user_id' => $user->id,
            'action_type' => $actionType,
            'emails_used' => $this->getEmailsProcessedThisMonth($user),
            'emails_remaining' => $this->getRemainingEmails($user),
        ]);
    }

    /**
     * Obtener porcentaje de uso del plan
     *
     * @param User $user
     * @return float
     */
    public function getUsagePercentage(User $user): float
    {
        if ($user->monthly_email_limit === 0) {
            return 0;
        }

        $used = $this->getEmailsProcessedThisMonth($user);
        $percentage = ($used / $user->monthly_email_limit) * 100;

        return round(min(100, $percentage), 2);
    }

    /**
     * Proyectar fecha en la que alcanzará el límite
     *
     * @param User $user
     * @return Carbon|null
     */
    public function projectLimitReachDate(User $user): ?Carbon
    {
        $used = $this->getEmailsProcessedThisMonth($user);
        $limit = $user->monthly_email_limit;
        $remaining = $limit - $used;

        if ($remaining <= 0) {
            return Carbon::now(); // Ya alcanzó el límite
        }

        // Calcular promedio de emails por día
        $startDate = $this->getBillingCycleStart($user);
        $daysElapsed = max(1, $startDate->diffInDays(now()));
        $avgPerDay = $used / $daysElapsed;

        if ($avgPerDay <= 0) {
            return null; // No hay suficiente data
        }

        // Calcular cuántos días faltan
        $daysUntilLimit = ceil($remaining / $avgPerDay);

        return Carbon::now()->addDays($daysUntilLimit);
    }

    /**
     * Obtener promedio de emails por semana
     *
     * @param User $user
     * @return float
     */
    public function getAverageEmailsPerWeek(User $user): float
    {
        $startDate = $this->getBillingCycleStart($user);
        $weeksElapsed = max(1, $startDate->diffInWeeks(now()));
        $used = $this->getEmailsProcessedThisMonth($user);

        return round($used / $weeksElapsed, 1);
    }

    /**
     * Resetear ciclo de facturación del usuario
     *
     * @param User $user
     * @return void
     */
    public function resetBillingCycle(User $user): void
    {
        // Resetear flag de todos los emails
        Email::where('user_id', $user->id)
            ->where('processed_this_billing_cycle', true)
            ->update([
                'processed_this_billing_cycle' => false,
            ]);

        // Actualizar fecha de inicio del ciclo
        $user->update([
            'billing_cycle_start' => Carbon::now()->startOfMonth(),
        ]);

        Log::info('🔄 Ciclo de facturación reseteado', [
            'user_id' => $user->id,
            'new_cycle_start' => $user->billing_cycle_start,
        ]);
    }

    /**
     * Obtener nivel de alerta según uso
     *
     * @param User $user
     * @return string ('normal', 'info', 'warning', 'critical', 'blocked')
     */
    public function getAlertLevel(User $user): string
    {
        $percentage = $this->getUsagePercentage($user);

        return match (true) {
            $percentage >= 100 => 'blocked',
            $percentage >= 90 => 'critical',
            $percentage >= 75 => 'warning',
            $percentage >= 50 => 'info',
            default => 'normal',
        };
    }

    /**
     * Verificar si debe enviar alerta al usuario
     *
     * @param User $user
     * @return bool
     */
    public function shouldSendAlert(User $user): bool
    {
        $level = $this->getAlertLevel($user);
        return in_array($level, ['warning', 'critical', 'blocked']);
    }

    // ============================================
    // HELPERS INTERNOS
    // ============================================

    /**
     * Convertir emails a tokens equivalentes
     *
     * @param int $emails
     * @return int
     */
    public function emailsToTokens(int $emails): int
    {
        return $emails * self::TOKENS_PER_EMAIL;
    }

    /**
     * Convertir tokens a emails equivalentes
     *
     * @param int $tokens
     * @return int
     */
    public function tokensToEmails(int $tokens): int
    {
        return (int) floor($tokens / self::TOKENS_PER_EMAIL);
    }

    /**
     * Obtener fecha de inicio del ciclo de facturación
     *
     * @param User $user
     * @return Carbon
     */
    protected function getBillingCycleStart(User $user): Carbon
    {
        // Si no tiene billing_cycle_start, usar inicio del mes actual
        if (!$user->billing_cycle_start) {
            return Carbon::now()->startOfMonth();
        }

        // Si billing_cycle_start es del mes pasado, actualizar al mes actual
        $cycleStart = Carbon::parse($user->billing_cycle_start);
        if ($cycleStart->month !== Carbon::now()->month) {
            $cycleStart = Carbon::now()->startOfMonth();
        }

        return $cycleStart;
    }

    /**
     * Obtener fecha de fin del ciclo de facturación
     *
     * @param User $user
     * @return Carbon
     */
    protected function getBillingCycleEnd(User $user): Carbon
    {
        return $this->getBillingCycleStart($user)->copy()->endOfMonth();
    }

    /**
     * Obtener límite de tokens mensual según plan
     *
     * @param User $user
     * @return int
     */
    public function getMonthlyTokenLimit(User $user): int
    {
        return $this->emailsToTokens($user->monthly_email_limit);
    }
}
