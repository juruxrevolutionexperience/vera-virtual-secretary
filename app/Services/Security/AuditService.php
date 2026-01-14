<?php

namespace App\Services\Security;

use App\Models\AuditLog;
use App\Models\SecurityEvent;
use App\Models\User;
use Illuminate\Support\Collection;

class AuditService
{
    /**
     * Registrar acción en audit log
     */
    public function log(
        string $action,
        ?int $userId = null,
        ?string $modelType = null,
        ?int $modelId = null,
        ?array $changes = null,
        ?array $metadata = null
    ): AuditLog {
        return AuditLog::record(
            $action,
            $userId,
            $modelType,
            $modelId,
            $changes,
            request()->ip(),
            request()->userAgent(),
            $metadata
        );
    }

    /**
     * Obtener logs de un usuario
     */
    public function getUserLogs(User $user, int $limit = 50): Collection
    {
        return AuditLog::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Obtener logs de un modelo específico
     */
    public function getModelLogs(string $modelType, int $modelId, int $limit = 50): Collection
    {
        return AuditLog::forModel($modelType, $modelId)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Obtener eventos de seguridad recientes
     */
    public function getRecentSecurityEvents(int $limit = 50): Collection
    {
        return SecurityEvent::orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Obtener eventos críticos pendientes
     */
    public function getCriticalEvents(): Collection
    {
        return SecurityEvent::critical()
            ->pending()
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Marcar evento como atendido
     */
    public function acknowledgeEvent(SecurityEvent $event, User $user): void
    {
        $event->update([
            'actioned_at' => now(),
            'actioned_by' => $user->id,
        ]);
    }

    /**
     * Obtener estadísticas de auditoría
     */
    public function getStats(?\Carbon\Carbon $from = null, ?\Carbon\Carbon $to = null): array
    {
        $from = $from ?? now()->subDays(30);
        $to = $to ?? now();

        $logs = AuditLog::whereBetween('created_at', [$from, $to])->get();
        $events = SecurityEvent::whereBetween('created_at', [$from, $to])->get();

        return [
            'total_actions' => $logs->count(),
            'unique_users' => $logs->pluck('user_id')->unique()->count(),
            'by_action' => $logs->groupBy('action')->map->count()->toArray(),
            'security_events' => [
                'total' => $events->count(),
                'by_severity' => $events->groupBy('severity')->map->count()->toArray(),
                'critical_pending' => $events->where('severity', 'critical')
                    ->where('requires_action', true)
                    ->whereNull('actioned_at')
                    ->count(),
            ],
        ];
    }
}
