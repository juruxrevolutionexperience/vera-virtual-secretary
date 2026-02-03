<?php

namespace App\Services;

use App\Models\ScheduledEmail;
use App\Models\EmailTemplate;
use App\Models\User;
use App\Models\EmailAccount;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;
use Carbon\Carbon;

class ScheduledEmailService
{
    /**
     * Programar un nuevo email
     */
    public function scheduleEmail(User $user, array $data): ScheduledEmail
    {
        // Validar cuenta de email
        $emailAccount = EmailAccount::findOrFail($data['email_account_id']);

        if ($emailAccount->user_id !== $user->id) {
            throw new \Exception('Email account does not belong to user');
        }

        // Si usa template, renderizar contenido
        if (isset($data['email_template_id'])) {
            $template = EmailTemplate::findOrFail($data['email_template_id']);
            $rendered = $template->render($data['template_data'] ?? []);

            $data['subject'] = $rendered['subject'];
            $data['body'] = $rendered['body'];
        }

        $scheduledEmail = ScheduledEmail::create([
            'user_id' => $user->id,
            'email_account_id' => $data['email_account_id'],
            'original_email_id' => $data['original_email_id'] ?? null,
            'email_template_id' => $data['email_template_id'] ?? null,
            'to' => is_array($data['to']) ? implode(',', $data['to']) : $data['to'],
            'cc' => isset($data['cc']) ? (is_array($data['cc']) ? implode(',', $data['cc']) : $data['cc']) : null,
            'bcc' => isset($data['bcc']) ? (is_array($data['bcc']) ? implode(',', $data['bcc']) : $data['bcc']) : null,
            'subject' => $data['subject'],
            'body' => $data['body'],
            'body_html' => $data['body_html'] ?? null,
            'attachments' => $data['attachments'] ?? null,
            'scheduled_at' => Carbon::parse($data['scheduled_at']),
            'metadata' => $data['metadata'] ?? null,
            'max_retries' => $data['max_retries'] ?? 3,
        ]);

        Log::info('📅 Email programado', [
            'scheduled_email_id' => $scheduledEmail->id,
            'to' => $scheduledEmail->to,
            'scheduled_at' => $scheduledEmail->scheduled_at,
            'user_id' => $user->id,
        ]);

        return $scheduledEmail;
    }

    /**
     * Actualizar email programado (solo si está pendiente)
     */
    public function updateScheduledEmail(ScheduledEmail $scheduledEmail, array $data): ScheduledEmail
    {
        if ($scheduledEmail->status !== 'pending') {
            throw new \Exception('Cannot update email that is not pending');
        }

        // Convertir arrays a strings si es necesario
        if (isset($data['to'])) {
            if (is_array($data['to'])) {
                // Filtrar vacíos y hacer implode
                $data['to'] = implode(',', array_filter($data['to']));
            }
            // Si ya es string, dejarlo como está
        }
        if (isset($data['cc'])) {
            if (is_array($data['cc'])) {
                $data['cc'] = implode(',', array_filter($data['cc']));
            } elseif (empty($data['cc'])) {
                $data['cc'] = null;
            }
        }
        if (isset($data['bcc'])) {
            if (is_array($data['bcc'])) {
                $data['bcc'] = implode(',', array_filter($data['bcc']));
            } elseif (empty($data['bcc'])) {
                $data['bcc'] = null;
            }
        }

        if (isset($data['scheduled_at'])) {
            $data['scheduled_at'] = Carbon::parse($data['scheduled_at']);
        }

        $scheduledEmail->update($data);

        Log::info('✏️ Email programado actualizado', [
            'scheduled_email_id' => $scheduledEmail->id,
        ]);

        return $scheduledEmail->fresh();
    }

    /**
     * Cancelar email programado
     */
    public function cancelScheduledEmail(ScheduledEmail $scheduledEmail): ScheduledEmail
    {
        if ($scheduledEmail->status !== 'pending') {
            throw new \Exception('Cannot cancel email that is not pending');
        }

        $scheduledEmail->markAsCancelled();

        Log::info('❌ Email programado cancelado', [
            'scheduled_email_id' => $scheduledEmail->id,
        ]);

        return $scheduledEmail;
    }

    /**
     * Obtener emails programados del usuario
     */
    public function getUserScheduledEmails(User $user, ?string $status = null): Collection
    {
        $query = ScheduledEmail::forUser($user->id)
            ->with(['emailAccount', 'template', 'originalEmail'])
            ->orderBy('scheduled_at', 'desc');

        if ($status) {
            $query->where('status', $status);
        }

        return $query->get();
    }

    /**
     * Obtener emails listos para enviar
     */
    public function getEmailsReadyToSend(): Collection
    {
        return ScheduledEmail::readyToSend()
            ->with(['emailAccount', 'user'])
            ->get();
    }

    /**
     * Procesar envío de un email programado
     */
    public function processScheduledEmail(ScheduledEmail $scheduledEmail): bool
    {
        try {
            Log::info('📤 Procesando email programado', [
                'scheduled_email_id' => $scheduledEmail->id,
            ]);

            // Aquí iría la lógica de envío usando GmailSyncService o OutlookSyncService
            // Por ahora simularemos el envío exitoso

            $this->sendEmail($scheduledEmail);

            $scheduledEmail->markAsSent();

            Log::info('✅ Email programado enviado', [
                'scheduled_email_id' => $scheduledEmail->id,
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('❌ Error enviando email programado', [
                'scheduled_email_id' => $scheduledEmail->id,
                'error' => $e->getMessage(),
            ]);

            $scheduledEmail->markAsFailed($e->getMessage());
            $scheduledEmail->incrementRetry();

            return false;
        }
    }

    /**
     * Reintentar email fallido
     */
    public function retryFailedEmail(ScheduledEmail $scheduledEmail): bool
    {
        if (!$scheduledEmail->canRetry()) {
            throw new \Exception('Email cannot be retried (max retries reached or wrong status)');
        }

        return $this->processScheduledEmail($scheduledEmail);
    }

    /**
     * Enviar email (integración con servicios de correo)
     */
    protected function sendEmail(ScheduledEmail $scheduledEmail): void
    {
        $emailAccount = $scheduledEmail->emailAccount;

        // TODO: Integrar con GmailSyncService o OutlookSyncService según el tipo de cuenta
        // Por ahora, esto es un placeholder

        Log::info('Enviando email programado...', [
            'account_type' => $emailAccount->provider,
            'to' => $scheduledEmail->to,
            'subject' => $scheduledEmail->subject,
        ]);

        // Simular envío exitoso
        // En producción, aquí se llamaría a GmailSyncService o OutlookSyncService
    }

    /**
     * Obtener estadísticas de emails programados
     */
    public function getStats(User $user): array
    {
        $emails = ScheduledEmail::forUser($user->id)->get();

        return [
            'total' => $emails->count(),
            'pending' => $emails->where('status', 'pending')->count(),
            'sent' => $emails->where('status', 'sent')->count(),
            'failed' => $emails->where('status', 'failed')->count(),
            'cancelled' => $emails->where('status', 'cancelled')->count(),
            'upcoming_today' => $emails->where('status', 'pending')
                ->whereBetween('scheduled_at', [now()->startOfDay(), now()->endOfDay()])
                ->count(),
            'upcoming_week' => $emails->where('status', 'pending')
                ->whereBetween('scheduled_at', [now(), now()->addWeek()])
                ->count(),
        ];
    }

    /**
     * Eliminar emails antiguos enviados/cancelados
     */
    public function cleanupOldEmails(int $daysOld = 30): int
    {
        $deleted = ScheduledEmail::whereIn('status', ['sent', 'cancelled'])
            ->where('updated_at', '<', now()->subDays($daysOld))
            ->delete();

        Log::info('🧹 Emails programados antiguos eliminados', [
            'count' => $deleted,
            'days_old' => $daysOld,
        ]);

        return $deleted;
    }
}
