<?php

namespace App\Services;

use App\Models\Email;
use App\Models\FollowUpReminder;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class FollowUpService
{
    /**
     * Crear un recordatorio
     */
    public function createReminder(User $user, Email $email, array $data): FollowUpReminder
    {
        return FollowUpReminder::create([
            'user_id' => $user->id,
            'email_id' => $email->id,
            'title' => $data['title'],
            'notes' => $data['notes'] ?? null,
            'remind_at' => $data['remind_at'],
            'is_recurring' => $data['is_recurring'] ?? false,
            'recurrence_type' => $data['recurrence_type'] ?? null,
            'recurrence_interval' => $data['recurrence_interval'] ?? null,
            'recurrence_end_date' => $data['recurrence_end_date'] ?? null,
            'notification_channels' => $data['notification_channels'] ?? ['in_app'],
            'metadata' => $data['metadata'] ?? null,
        ]);
    }

    /**
     * Actualizar recordatorio
     */
    public function updateReminder(FollowUpReminder $reminder, array $data): FollowUpReminder
    {
        $reminder->update($data);
        return $reminder->fresh();
    }

    /**
     * Posponer recordatorio
     */
    public function snoozeReminder(FollowUpReminder $reminder, int $minutes): FollowUpReminder
    {
        $reminder->snooze($minutes);
        return $reminder->fresh();
    }

    /**
     * Marcar recordatorio como enviado
     */
    public function markAsSent(FollowUpReminder $reminder): FollowUpReminder
    {
        $reminder->markAsSent();
        return $reminder->fresh();
    }

    /**
     * Marcar recordatorio como completado
     */
    public function markAsCompleted(FollowUpReminder $reminder): FollowUpReminder
    {
        $reminder->markAsCompleted();
        return $reminder->fresh();
    }

    /**
     * Cancelar recordatorio
     */
    public function cancelReminder(FollowUpReminder $reminder): FollowUpReminder
    {
        $reminder->cancel();
        return $reminder->fresh();
    }

    /**
     * Reactivar recordatorio
     */
    public function reactivateReminder(FollowUpReminder $reminder): FollowUpReminder
    {
        $reminder->reactivate();
        return $reminder->fresh();
    }

    /**
     * Eliminar recordatorio
     */
    public function deleteReminder(FollowUpReminder $reminder): bool
    {
        return $reminder->delete();
    }

    /**
     * Obtener recordatorios pendientes del usuario
     */
    public function getPendingReminders(User $user): Collection
    {
        return FollowUpReminder::where('user_id', $user->id)
            ->pending()
            ->with('email')
            ->orderBy('remind_at')
            ->get();
    }

    /**
     * Obtener recordatorios listos para enviar
     */
    public function getReadyReminders(User $user): Collection
    {
        return FollowUpReminder::where('user_id', $user->id)
            ->readyToSend()
            ->with('email')
            ->get();
    }

    /**
     * Obtener recordatorios próximos
     */
    public function getUpcomingReminders(User $user, int $days = 7): Collection
    {
        return FollowUpReminder::where('user_id', $user->id)
            ->upcoming($days)
            ->with('email')
            ->get();
    }

    /**
     * Obtener recordatorios vencidos
     */
    public function getOverdueReminders(User $user): Collection
    {
        return FollowUpReminder::where('user_id', $user->id)
            ->overdue()
            ->with('email')
            ->get();
    }

    /**
     * Procesar recordatorios listos para enviar
     */
    public function processReadyReminders(?User $user = null): array
    {
        $stats = [
            'reminders_processed' => 0,
            'notifications_sent' => 0,
            'errors' => 0,
        ];

        try {
            $query = FollowUpReminder::readyToSend()->with('email', 'user');

            if ($user) {
                $query->where('user_id', $user->id);
            }

            $reminders = $query->get();

            foreach ($reminders as $reminder) {
                try {
                    // Enviar notificaciones
                    $notificationsSent = $this->sendNotifications($reminder);
                    $stats['notifications_sent'] += $notificationsSent;

                    // Marcar como enviado
                    $reminder->markAsSent();

                    $stats['reminders_processed']++;

                    Log::info('Reminder processed', [
                        'reminder_id' => $reminder->id,
                        'user_id' => $reminder->user_id,
                        'email_id' => $reminder->email_id,
                    ]);
                } catch (\Exception $e) {
                    $stats['errors']++;
                    Log::error('Error processing reminder', [
                        'reminder_id' => $reminder->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        } catch (\Exception $e) {
            Log::error('Error in processReadyReminders', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }

        return $stats;
    }

    /**
     * Enviar notificaciones del recordatorio
     */
    protected function sendNotifications(FollowUpReminder $reminder): int
    {
        $count = 0;

        try {
            // Notificación in-app (crear alerta)
            if ($reminder->shouldNotifyInApp()) {
                $alertService = app(EmailAlertService::class);
                $alertService->createAlert($reminder->user, [
                    'email_id' => $reminder->email_id,
                    'type' => 'follow_up',
                    'severity' => 'medium',
                    'title' => $reminder->title,
                    'message' => $reminder->notes ?? "Recordatorio de seguimiento",
                    'metadata' => [
                        'reminder_id' => $reminder->id,
                    ],
                ]);
                $count++;
            }

            // Notificación por email
            if ($reminder->shouldNotifyByEmail()) {
                // TODO: Implementar envío de email
                Log::info('Email notification would be sent', [
                    'reminder_id' => $reminder->id,
                    'user_id' => $reminder->user_id,
                ]);
                $count++;
            }

            // Notificación push
            if ($reminder->shouldNotifyByPush()) {
                // TODO: Implementar notificación push
                Log::info('Push notification would be sent', [
                    'reminder_id' => $reminder->id,
                    'user_id' => $reminder->user_id,
                ]);
                $count++;
            }
        } catch (\Exception $e) {
            Log::error('Error sending notifications', [
                'reminder_id' => $reminder->id,
                'error' => $e->getMessage(),
            ]);
        }

        return $count;
    }

    /**
     * Obtener estadísticas de recordatorios
     */
    public function getStats(User $user): array
    {
        $total = FollowUpReminder::where('user_id', $user->id)->count();
        $pending = FollowUpReminder::where('user_id', $user->id)->pending()->count();
        $completed = FollowUpReminder::where('user_id', $user->id)->completed()->count();
        $cancelled = FollowUpReminder::where('user_id', $user->id)->cancelled()->count();
        $overdue = FollowUpReminder::where('user_id', $user->id)->overdue()->count();
        $upcoming = FollowUpReminder::where('user_id', $user->id)->upcoming(7)->count();
        $recurring = FollowUpReminder::where('user_id', $user->id)->recurring()->count();

        return [
            'total' => $total,
            'pending' => $pending,
            'completed' => $completed,
            'cancelled' => $cancelled,
            'overdue' => $overdue,
            'upcoming_7_days' => $upcoming,
            'recurring' => $recurring,
        ];
    }

    /**
     * Limpiar recordatorios completados antiguos
     */
    public function cleanOldCompleted(User $user, int $daysOld = 30): int
    {
        return FollowUpReminder::where('user_id', $user->id)
            ->completed()
            ->where('completed_at', '<', now()->subDays($daysOld))
            ->delete();
    }

    /**
     * Crear recordatorios automáticos para emails importantes
     */
    public function createAutoReminders(User $user, int $hoursAfter = 48): int
    {
        $remindersCreated = 0;

        // Buscar emails importantes sin respuesta
        $emails = Email::where('user_id', $user->id)
            ->whereIn('priority', ['high', 'urgent'])
            ->whereNull('replied_at')
            ->where('received_at', '>=', now()->subDays(7))
            ->where('is_archived', false)
            ->get();

        foreach ($emails as $email) {
            // Verificar si ya existe un recordatorio para este email
            $existingReminder = FollowUpReminder::where('email_id', $email->id)
                ->whereIn('status', ['pending', 'sent'])
                ->first();

            if (!$existingReminder) {
                $this->createReminder($user, $email, [
                    'title' => "Seguimiento: {$email->subject}",
                    'notes' => "Email de {$email->from_name} requiere seguimiento",
                    'remind_at' => now()->addHours($hoursAfter),
                    'notification_channels' => ['in_app'],
                ]);

                $remindersCreated++;
            }
        }

        return $remindersCreated;
    }
}
