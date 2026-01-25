<?php

namespace App\Services;

use App\Models\Email;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;

class EmailWorkflowService
{
    /**
     * Cambiar estado de workflow de un email
     */
    public function changeWorkflowStatus(Email $email, string $newStatus, ?string $notes = null): Email
    {
        $oldStatus = $email->workflow_status;

        $email->setWorkflowStatus($newStatus);

        if ($notes) {
            $email->update(['action_notes' => $notes]);
        }

        Log::info('🔄 Estado de workflow cambiado', [
            'email_id' => $email->id,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'notes' => $notes,
        ]);

        return $email->fresh();
    }

    /**
     * Marcar email como requiere acción
     */
    public function markAsRequiringAction(Email $email, string $notes): Email
    {
        $email->markAsRequiringAction($notes);

        Log::info('⚠️  Email marcado como requiere acción', [
            'email_id' => $email->id,
            'notes' => $notes,
        ]);

        return $email->fresh();
    }

    /**
     * Completar acción de un email
     */
    public function completeAction(Email $email): Email
    {
        $email->markActionCompleted();

        Log::info('✅ Acción de email completada', [
            'email_id' => $email->id,
        ]);

        return $email->fresh();
    }

    /**
     * Posponer email (snooze)
     */
    public function snoozeEmail(Email $email, \DateTimeInterface $until): Email
    {
        $email->snoozeUntil($until);

        Log::info('💤 Email pospuesto', [
            'email_id' => $email->id,
            'snoozed_until' => $until->format('Y-m-d H:i:s'),
        ]);

        return $email->fresh();
    }

    /**
     * Despertar email del snooze
     */
    public function wakeupEmail(Email $email): Email
    {
        $email->wakeup();

        Log::info('⏰ Email despertado del snooze', [
            'email_id' => $email->id,
        ]);

        return $email->fresh();
    }

    /**
     * Procesar emails listos para despertar
     */
    public function processWakeups(): int
    {
        $emails = Email::readyToWakeup()->get();

        foreach ($emails as $email) {
            $this->wakeupEmail($email);
        }

        Log::info('⏰ Emails despertados automáticamente', [
            'count' => $emails->count(),
        ]);

        return $emails->count();
    }

    /**
     * Establecer fecha de vencimiento
     */
    public function setDueDate(Email $email, ?\DateTimeInterface $dueDate): Email
    {
        if ($dueDate === null) {
            // Quitar la fecha de vencimiento
            $email->update(['due_date' => null]);

            Log::info('📅 Fecha de vencimiento eliminada', [
                'email_id' => $email->id,
            ]);
        } else {
            $email->setDueDate($dueDate);

            Log::info('📅 Fecha de vencimiento establecida', [
                'email_id' => $email->id,
                'due_date' => $dueDate->format('Y-m-d H:i:s'),
            ]);
        }

        return $email->fresh();
    }

    /**
     * Obtener emails vencidos del usuario
     */
    public function getOverdueEmails(User $user): Collection
    {
        return Email::where('user_id', $user->id)
            ->overdue()
            ->orderBy('due_date', 'asc')
            ->get();
    }

    /**
     * Obtener emails que vencen pronto
     */
    public function getDueSoonEmails(User $user, int $hours = 24): Collection
    {
        return Email::where('user_id', $user->id)
            ->dueSoon($hours)
            ->orderBy('due_date', 'asc')
            ->get();
    }

    /**
     * Obtener resumen de workflow del usuario
     */
    public function getWorkflowSummary(User $user): array
    {
        $baseQuery = Email::where('user_id', $user->id)->active();

        return [
            'unread' => (clone $baseQuery)->inWorkflow('unread')->count(),
            'pending_validation' => (clone $baseQuery)->inWorkflow('pending_validation')->count(),
            'pending_response' => (clone $baseQuery)->inWorkflow('pending_response')->count(),
            'in_progress' => (clone $baseQuery)->inWorkflow('in_progress')->count(),
            'in_review' => (clone $baseQuery)->inWorkflow('in_review')->count(),
            'approved' => (clone $baseQuery)->inWorkflow('approved')->count(),
            'completed' => (clone $baseQuery)->inWorkflow('completed')->count(),
            'archived' => (clone $baseQuery)->inWorkflow('archived')->count(),
            'no_action_needed' => (clone $baseQuery)->inWorkflow('no_action_needed')->count(),
            'requiring_action' => (clone $baseQuery)->requireingAction()->count(),
            'overdue' => (clone $baseQuery)->overdue()->count(),
            'snoozed' => (clone $baseQuery)->snoozed()->count(),
        ];
    }

    /**
     * Mover email a papelera
     */
    public function moveToTrash(Email $email): Email
    {
        $email->moveToTrash();

        Log::info('🗑️  Email movido a papelera', [
            'email_id' => $email->id,
        ]);

        return $email->fresh();
    }

    /**
     * Restaurar email de la papelera
     */
    public function restoreFromTrash(Email $email): Email
    {
        $email->restoreFromTrash();

        Log::info('♻️  Email restaurado de papelera', [
            'email_id' => $email->id,
        ]);

        return $email->fresh();
    }

    /**
     * Eliminar permanentemente emails de la papelera
     */
    public function emptyTrash(User $user): int
    {
        $deletedCount = Email::where('user_id', $user->id)
            ->deleted()
            ->forceDelete();

        Log::info('🗑️  Papelera vaciada', [
            'user_id' => $user->id,
            'deleted_count' => $deletedCount,
        ]);

        return $deletedCount;
    }

    /**
     * Limpiar automáticamente papelera (emails > 30 días)
     */
    public function cleanOldTrash(int $days = 30): int
    {
        $cutoffDate = now()->subDays($days);

        $deletedCount = Email::deleted()
            ->where('deleted_at_custom', '<', $cutoffDate)
            ->forceDelete();

        Log::info('🧹 Papelera limpiada automáticamente', [
            'days' => $days,
            'deleted_count' => $deletedCount,
        ]);

        return $deletedCount;
    }

    /**
     * Cambiar estado de workflow en masa
     */
    public function bulkChangeWorkflowStatus(array $emailIds, string $newStatus, User $user): int
    {
        $updatedCount = 0;

        foreach ($emailIds as $emailId) {
            try {
                $email = Email::where('id', $emailId)
                    ->where('user_id', $user->id)
                    ->firstOrFail();

                $this->changeWorkflowStatus($email, $newStatus);
                $updatedCount++;
            } catch (\Exception $e) {
                Log::warning('Error cambiando workflow en masa', [
                    'email_id' => $emailId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('🔄 Workflow cambiado en masa', [
            'count' => $updatedCount,
            'new_status' => $newStatus,
        ]);

        return $updatedCount;
    }

    /**
     * Mover múltiples emails a papelera
     */
    public function bulkMoveToTrash(array $emailIds, User $user): int
    {
        $movedCount = 0;

        foreach ($emailIds as $emailId) {
            try {
                $email = Email::where('id', $emailId)
                    ->where('user_id', $user->id)
                    ->firstOrFail();

                $this->moveToTrash($email);
                $movedCount++;
            } catch (\Exception $e) {
                Log::warning('Error moviendo a papelera en masa', [
                    'email_id' => $emailId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $movedCount;
    }
}
