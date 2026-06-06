<?php

namespace App\Services;

use App\Models\User;
use App\Models\VeraNotification;
use App\Models\VeraNotificationPreference;
use App\Services\VeraAnalyticsService;
use App\Services\VeraUsageService;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * VeraNotificationService
 *
 * FASE 4: Sistema de notificaciones
 */
class VeraNotificationService
{
    protected VeraAnalyticsService $analyticsService;
    protected VeraUsageService $usageService;

    public function __construct()
    {
        $this->analyticsService = new VeraAnalyticsService();
        $this->usageService = new VeraUsageService();
    }

    /**
     * Enviar resumen diario
     */
    public function sendDailySummary(User $user): ?VeraNotification
    {
        $prefs = VeraNotificationPreference::getOrCreateForUser($user);

        if (!$prefs->daily_summary_enabled) {
            return null;
        }

        // Obtener estadísticas del día
        $startDate = Carbon::today();
        $endDate = Carbon::now();

        $stats = $this->analyticsService->getActivityStats($user, $startDate, $endDate);

        $message = $this->buildDailySummaryMessage($user, $stats);

        return $this->createNotification([
            'user_id' => $user->id,
            'type' => 'daily_summary',
            'title' => 'Resumen Diario de Vera',
            'message' => $message,
            'data' => ['stats' => $stats],
            'channel' => $prefs->email_enabled ? 'email' : 'push',
        ]);
    }

    /**
     * Enviar resumen semanal
     */
    public function sendWeeklySummary(User $user): ?VeraNotification
    {
        $prefs = VeraNotificationPreference::getOrCreateForUser($user);

        if (!$prefs->weekly_summary_enabled) {
            return null;
        }

        $startDate = Carbon::now()->startOfWeek();
        $endDate = Carbon::now();

        $stats = $this->analyticsService->getActivityStats($user, $startDate, $endDate);
        $prediction = $this->analyticsService->predictMonthlyUsage($user);

        $message = $this->buildWeeklySummaryMessage($user, $stats, $prediction);

        return $this->createNotification([
            'user_id' => $user->id,
            'type' => 'weekly_summary',
            'title' => 'Resumen Semanal de Vera',
            'message' => $message,
            'data' => [
                'stats' => $stats,
                'prediction' => $prediction,
            ],
            'channel' => $prefs->email_enabled ? 'email' : 'push',
        ]);
    }

    /**
     * Enviar resumen mensual
     */
    public function sendMonthlySummary(User $user): ?VeraNotification
    {
        $prefs = VeraNotificationPreference::getOrCreateForUser($user);

        if (!$prefs->monthly_summary_enabled) {
            return null;
        }

        $startDate = Carbon::now()->startOfMonth();
        $endDate = Carbon::now();

        $stats = $this->analyticsService->getActivityStats($user, $startDate, $endDate);
        $comparison = $this->analyticsService->getMonthComparison($user);

        $message = $this->buildMonthlySummaryMessage($user, $stats, $comparison);

        return $this->createNotification([
            'user_id' => $user->id,
            'type' => 'monthly_summary',
            'title' => 'Resumen Mensual de Vera',
            'message' => $message,
            'data' => [
                'stats' => $stats,
                'comparison' => $comparison,
            ],
            'channel' => $prefs->email_enabled ? 'email' : 'push',
        ]);
    }

    /**
     * Enviar alerta de límite
     */
    public function sendLimitWarning(User $user, int $percentage): ?VeraNotification
    {
        $prefs = VeraNotificationPreference::getOrCreateForUser($user);

        if (!$prefs->limit_warnings_enabled) {
            return null;
        }

        $usage = $this->usageService->calculateMonthlyUsage($user);

        $message = "Has utilizado {$usage['emails_processed']} de {$usage['email_limit']} emails este mes ({$percentage}%). Considera actualizar tu plan si necesitas más capacidad.";

        return $this->createNotification([
            'user_id' => $user->id,
            'type' => 'limit_warning',
            'title' => 'Advertencia: Límite de Emails al ' . $percentage . '%',
            'message' => $message,
            'data' => ['usage' => $usage],
            'channel' => 'both',
        ]);
    }

    /**
     * Avisar al dueño de que Vera ha escalado una queja/incidencia de un cliente.
     * Email + aviso in-app inmediato: una queja grave no puede esperar a que el
     * dueño entre al panel por casualidad.
     */
    public function sendEscalationAlert(User $user, string $contactName, string $clientMessage): ?VeraNotification
    {
        $resumen = mb_substr(trim(preg_replace('/\s+/', ' ', $clientMessage)), 0, 400);

        $message = "Vera ha detectado una queja o incidencia de {$contactName} y la ha escalado para que la gestiones personalmente.\n\n"
            . "Mensaje del cliente:\n\"{$resumen}\"\n\n"
            . "Te recomendamos contactar con el cliente cuanto antes.";

        return $this->createNotification([
            'user_id' => $user->id,
            'type'    => 'escalation_alert',
            'title'   => '⚠️ Queja escalada — ' . $contactName,
            'message' => $message,
            'data'    => ['contact' => $contactName],
            'channel' => 'both',
        ]);
    }

    /**
     * Crear notificación
     */
    protected function createNotification(array $data): VeraNotification
    {
        $notification = VeraNotification::create($data);

        // Enviar inmediatamente si no está programada
        if (!isset($data['scheduled_for'])) {
            $this->sendNotification($notification);
        }

        return $notification;
    }

    /**
     * Enviar notificación
     */
    public function sendNotification(VeraNotification $notification): bool
    {
        try {
            if (in_array($notification->channel, ['email', 'both'])) {
                $this->sendEmail($notification);
            }

            if (in_array($notification->channel, ['push', 'both'])) {
                $this->sendPushNotification($notification);
            }

            $notification->markAsSent();
            return true;

        } catch (\Exception $e) {
            Log::error('Error enviando notificación', [
                'notification_id' => $notification->id,
                'error' => $e->getMessage(),
            ]);

            $notification->update(['status' => 'failed']);
            return false;
        }
    }

    /**
     * Enviar email
     */
    protected function sendEmail(VeraNotification $notification): void
    {
        Mail::to($notification->user->email)->send(
            new \App\Mail\VeraNotificationMail($notification)
        );
    }

    /**
     * Enviar notificación push (placeholder)
     */
    protected function sendPushNotification(VeraNotification $notification): void
    {
        // TODO: Implementar con un servicio de push notifications
        Log::info('Push notification enviada', [
            'notification_id' => $notification->id,
            'title' => $notification->title,
        ]);
    }

    /**
     * Procesar notificaciones pendientes
     */
    public function processScheduledNotifications(): int
    {
        $notifications = VeraNotification::pending()
            ->scheduledNow()
            ->get();

        $sent = 0;
        foreach ($notifications as $notification) {
            if ($this->sendNotification($notification)) {
                $sent++;
            }
        }

        return $sent;
    }

    /**
     * Build message helpers
     */
    protected function buildDailySummaryMessage(User $user, array $stats): string
    {
        return <<<MSG
Hola {$user->name},

Aquí está tu resumen de actividad de hoy:

📧 Emails procesados: {$stats['total_activities']}
✉️ Respuestas generadas: {$stats['emails_replied']}
⚡ Tokens consumidos: {$stats['total_tokens']}
💰 Costo: \${$stats['total_cost']}

¡Que tengas un excelente día!
MSG;
    }

    protected function buildWeeklySummaryMessage(User $user, array $stats, array $prediction): string
    {
        return <<<MSG
Hola {$user->name},

Resumen de la semana:

📊 Total de actividades: {$stats['total_activities']}
📧 Emails procesados: {$stats['emails_processed']}
✉️ Respuestas generadas: {$stats['emails_replied']}
⚡ Tokens consumidos: {$stats['total_tokens']}

📈 Proyección del mes: {$prediction['projected_end_of_month']['emails']} emails
MSG;
    }

    protected function buildMonthlySummaryMessage(User $user, array $stats, array $comparison): string
    {
        $change = $comparison['change']['activities'];
        $trend = $change['direction'] === 'up' ? '📈' : '📉';

        return <<<MSG
Hola {$user->name},

Resumen del mes:

📊 Total de actividades: {$stats['total_activities']} {$trend} {$change['percentage']}%
📧 Emails procesados: {$stats['emails_processed']}
✉️ Respuestas generadas: {$stats['emails_replied']}
⚡ Tokens consumidos: {$stats['total_tokens']}
💰 Costo total: \${$stats['total_cost']}

¡Gracias por usar Vera!
MSG;
    }
}
