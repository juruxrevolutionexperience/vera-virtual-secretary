<?php

namespace App\Services;

use App\Models\Email;
use App\Models\EmailAlert;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class EmailAlertService
{
    /**
     * Crear una alerta
     */
    public function createAlert(User $user, array $data): EmailAlert
    {
        $alertData = [
            'user_id' => $user->id,
            'email_id' => $data['email_id'] ?? null,
            'type' => $data['type'],
            'severity' => $data['severity'] ?? 'medium',
            'title' => $data['title'],
            'message' => $data['message'],
            'metadata' => $data['metadata'] ?? null,
            'status' => $data['status'] ?? 'pending',
            'alert_at' => $data['alert_at'] ?? now(),
            'expires_at' => $data['expires_at'] ?? null,
        ];

        return EmailAlert::create($alertData);
    }

    /**
     * Verificar y crear alertas de deadlines
     */
    public function checkDeadlineAlerts(User $user): int
    {
        $alertsCreated = 0;

        // Buscar emails con due_date próximo (próximas 24 horas) que no han sido atendidos
        $emails = Email::where('user_id', $user->id)
            ->whereNotNull('due_date')
            ->where('due_date', '<=', now()->addHours(24))
            ->where('workflow_status', '!=', 'completed')
            ->get();

        foreach ($emails as $email) {
            // Verificar si ya existe una alerta para este email
            $existingAlert = EmailAlert::where('email_id', $email->id)
                ->where('type', 'deadline')
                ->active()
                ->first();

            if (!$existingAlert) {
                $hoursUntilDue = now()->diffInHours($email->due_date, false);
                $severity = $this->calculateDeadlineSeverity($hoursUntilDue);

                $this->createAlert($user, [
                    'email_id' => $email->id,
                    'type' => 'deadline',
                    'severity' => $severity,
                    'title' => 'Email con fecha de vencimiento próxima',
                    'message' => "El email '{$email->subject}' vence en " . abs($hoursUntilDue) . " horas",
                    'metadata' => [
                        'due_date' => $email->due_date,
                        'hours_until_due' => $hoursUntilDue,
                    ],
                ]);

                $alertsCreated++;
            }
        }

        return $alertsCreated;
    }

    /**
     * Calcular severidad según horas hasta vencimiento
     */
    protected function calculateDeadlineSeverity(float $hoursUntilDue): string
    {
        if ($hoursUntilDue < 0) {
            return 'critical';  // Ya venció
        } elseif ($hoursUntilDue <= 2) {
            return 'high';  // Menos de 2 horas
        } elseif ($hoursUntilDue <= 6) {
            return 'medium';  // Menos de 6 horas
        } else {
            return 'low';  // Más de 6 horas
        }
    }

    /**
     * Verificar y crear alertas de emails sin respuesta
     */
    public function checkNoResponseAlerts(User $user, int $hoursThreshold = 24): int
    {
        $alertsCreated = 0;

        // Buscar emails de alta prioridad sin respuesta después de X horas
        $emails = Email::where('user_id', $user->id)
            ->whereIn('priority', ['high', 'urgent'])
            ->whereNull('replied_at')
            ->where('received_at', '<=', now()->subHours($hoursThreshold))
            ->where('is_archived', false)
            ->get();

        foreach ($emails as $email) {
            // Verificar si ya existe una alerta
            $existingAlert = EmailAlert::where('email_id', $email->id)
                ->where('type', 'no_response')
                ->active()
                ->first();

            if (!$existingAlert) {
                $hoursSinceReceived = $email->received_at->diffInHours(now());

                $this->createAlert($user, [
                    'email_id' => $email->id,
                    'type' => 'no_response',
                    'severity' => $email->priority === 'urgent' ? 'critical' : 'high',
                    'title' => 'Email prioritario sin respuesta',
                    'message' => "El email '{$email->subject}' lleva {$hoursSinceReceived} horas sin respuesta",
                    'metadata' => [
                        'hours_since_received' => $hoursSinceReceived,
                        'email_priority' => $email->priority,
                    ],
                ]);

                $alertsCreated++;
            }
        }

        return $alertsCreated;
    }

    /**
     * Verificar emails vencidos
     */
    public function checkOverdueAlerts(User $user): int
    {
        $alertsCreated = 0;

        // Buscar emails vencidos
        $emails = Email::where('user_id', $user->id)
            ->whereNotNull('due_date')
            ->where('due_date', '<', now())
            ->where('workflow_status', '!=', 'completed')
            ->get();

        foreach ($emails as $email) {
            // Verificar si ya existe una alerta de overdue
            $existingAlert = EmailAlert::where('email_id', $email->id)
                ->where('type', 'overdue')
                ->active()
                ->first();

            if (!$existingAlert) {
                $this->createAlert($user, [
                    'email_id' => $email->id,
                    'type' => 'overdue',
                    'severity' => 'critical',
                    'title' => 'Email vencido',
                    'message' => "El email '{$email->subject}' está vencido",
                    'metadata' => [
                        'due_date' => $email->due_date,
                        'days_overdue' => $email->due_date->diffInDays(now()),
                    ],
                ]);

                $alertsCreated++;
            }
        }

        return $alertsCreated;
    }

    /**
     * Verificar emails de alta prioridad no atendidos
     */
    public function checkHighPriorityAlerts(User $user, int $hoursThreshold = 12): int
    {
        $alertsCreated = 0;

        // Buscar emails urgentes no leídos después de X horas
        $emails = Email::where('user_id', $user->id)
            ->where('priority', 'urgent')
            ->where('is_read', false)
            ->where('received_at', '<=', now()->subHours($hoursThreshold))
            ->where('is_archived', false)
            ->get();

        foreach ($emails as $email) {
            // Verificar si ya existe una alerta
            $existingAlert = EmailAlert::where('email_id', $email->id)
                ->where('type', 'high_priority')
                ->active()
                ->first();

            if (!$existingAlert) {
                $this->createAlert($user, [
                    'email_id' => $email->id,
                    'type' => 'high_priority',
                    'severity' => 'critical',
                    'title' => 'Email urgente sin leer',
                    'message' => "Email urgente de {$email->from_name}: '{$email->subject}'",
                    'metadata' => [
                        'hours_since_received' => $email->received_at->diffInHours(now()),
                    ],
                ]);

                $alertsCreated++;
            }
        }

        return $alertsCreated;
    }

    /**
     * Obtener alertas activas del usuario
     */
    public function getActiveAlerts(User $user, ?string $severity = null): Collection
    {
        $query = EmailAlert::where('user_id', $user->id)
            ->active()
            ->readyToShow()
            ->with('email');

        if ($severity) {
            $query->bySeverity($severity);
        }

        return $query->orderByRaw("
            CASE severity
                WHEN 'critical' THEN 1
                WHEN 'high' THEN 2
                WHEN 'medium' THEN 3
                WHEN 'low' THEN 4
            END
        ")
        ->orderBy('created_at', 'desc')
        ->get();
    }

    /**
     * Obtener alertas por tipo
     */
    public function getAlertsByType(User $user, string $type): Collection
    {
        return EmailAlert::where('user_id', $user->id)
            ->byType($type)
            ->active()
            ->with('email')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Obtener estadísticas de alertas
     */
    public function getStats(User $user): array
    {
        $total = EmailAlert::where('user_id', $user->id)->active()->count();
        $critical = EmailAlert::where('user_id', $user->id)->active()->critical()->count();
        $high = EmailAlert::where('user_id', $user->id)->active()->high()->count();

        $byType = EmailAlert::where('user_id', $user->id)
            ->active()
            ->selectRaw('type, COUNT(*) as count')
            ->groupBy('type')
            ->pluck('count', 'type')
            ->toArray();

        return [
            'total_active' => $total,
            'critical' => $critical,
            'high' => $high,
            'by_type' => $byType,
        ];
    }

    /**
     * Reconocer alerta
     */
    public function acknowledgeAlert(EmailAlert $alert): EmailAlert
    {
        $alert->acknowledge();
        return $alert->fresh();
    }

    /**
     * Resolver alerta
     */
    public function resolveAlert(EmailAlert $alert, ?string $notes = null): EmailAlert
    {
        $alert->resolve($notes);
        return $alert->fresh();
    }

    /**
     * Descartar alerta
     */
    public function dismissAlert(EmailAlert $alert): EmailAlert
    {
        $alert->dismiss();
        return $alert->fresh();
    }

    /**
     * Limpiar alertas expiradas
     */
    public function cleanExpiredAlerts(User $user): int
    {
        $expired = EmailAlert::where('user_id', $user->id)
            ->expired()
            ->active()
            ->get();

        $count = 0;
        foreach ($expired as $alert) {
            $alert->dismiss();
            $count++;
        }

        return $count;
    }

    /**
     * Limpiar alertas antiguas resueltas
     */
    public function cleanOldResolvedAlerts(User $user, int $daysOld = 30): int
    {
        return EmailAlert::where('user_id', $user->id)
            ->resolved()
            ->where('resolved_at', '<', now()->subDays($daysOld))
            ->delete();
    }

    /**
     * Ejecutar todas las verificaciones de alertas
     */
    public function runAllChecks(User $user): array
    {
        Log::info('Running all alert checks', ['user_id' => $user->id]);

        $stats = [
            'deadline_alerts' => $this->checkDeadlineAlerts($user),
            'no_response_alerts' => $this->checkNoResponseAlerts($user),
            'overdue_alerts' => $this->checkOverdueAlerts($user),
            'high_priority_alerts' => $this->checkHighPriorityAlerts($user),
            'expired_cleaned' => $this->cleanExpiredAlerts($user),
        ];

        Log::info('Alert checks completed', array_merge(['user_id' => $user->id], $stats));

        return $stats;
    }
}
