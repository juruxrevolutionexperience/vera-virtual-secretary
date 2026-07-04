<?php

namespace App\Console\Commands;

use App\Models\DataRetentionLog;
use App\Models\Meeting;
use App\Models\MeetingRequest;
use App\Models\VeraConversationMessage;
use Illuminate\Console\Command;

/**
 * Política de retención RGPD para datos de CLIENTES FINALES.
 *
 * Principio de limitación del plazo de conservación (art. 5.1.e RGPD):
 * el contenido de las conversaciones deja de ser necesario pasado un tiempo
 * razonable. Se anonimiza el CONTENIDO (lo personal) y se conservan los
 * registros agregados (fechas, contadores, tokens) para métricas y facturación.
 *
 * Corre mensualmente vía scheduler. Retención por defecto: 24 meses.
 */
class VeraRetentionCleanup extends Command
{
    protected $signature = 'vera:retention-cleanup
                            {--months=24 : Meses de retención del contenido de conversaciones}
                            {--dry-run : Mostrar qué se anonimizaría sin tocar nada}';

    protected $description = 'RGPD: anonimiza contenido de conversaciones más antiguas que el período de retención';

    private const PLACEHOLDER = '[contenido eliminado por política de retención]';

    public function handle(): int
    {
        $months = max(1, (int) $this->option('months'));
        $dryRun = (bool) $this->option('dry-run');
        $cutoff = now()->subMonths($months);

        $this->info("Retención: {$months} meses — anonimizando contenido anterior a {$cutoff->toDateString()}");
        if ($dryRun) {
            $this->warn('[DRY-RUN] No se modificará nada');
        }

        // ── Mensajes de conversación con contenido personal ──────────────────
        $msgQuery = VeraConversationMessage::where('created_at', '<', $cutoff)
            ->where('content', '!=', self::PLACEHOLDER);
        $msgCount = $msgQuery->count();
        $this->line("Mensajes a anonimizar: {$msgCount}");

        // ── Contexto de solicitudes antiguas (notas de reserva, texto libre) ──
        $reqQuery = MeetingRequest::where('created_at', '<', $cutoff)
            ->whereNotNull('context');
        $reqCount = $reqQuery->count();
        $this->line("Solicitudes con contexto a limpiar: {$reqCount}");

        // ── Notas de citas antiguas (texto libre del cliente) ─────────────────
        $meetingQuery = Meeting::where('scheduled_at', '<', $cutoff)
            ->whereNotNull('notes');
        $meetingCount = $meetingQuery->count();
        $this->line("Citas antiguas con notas a limpiar: {$meetingCount}");

        if ($dryRun) {
            $this->info('[DRY-RUN] Fin. Nada modificado.');
            return 0;
        }

        if ($msgCount === 0 && $reqCount === 0 && $meetingCount === 0) {
            $this->info('Nada que limpiar.');
            return 0;
        }

        $msgQuery->update(['content' => self::PLACEHOLDER, 'metadata' => null]);
        $reqQuery->update(['context' => null]);
        $meetingQuery->update(['notes' => null]);

        DataRetentionLog::create([
            'user_id'          => null,
            'data_type'        => 'conversation_content',
            'records_deleted'  => $msgCount + $reqCount + $meetingCount,
            'retention_period' => "{$months} meses",
            'trigger'          => 'scheduled_retention_policy',
            'metadata'         => [
                'messages' => $msgCount,
                'requests' => $reqCount,
                'meetings' => $meetingCount,
                'cutoff'   => $cutoff->toDateString(),
            ],
            'deleted_at'       => now(),
        ]);

        $this->info('✅ Retención aplicada y registrada en data_retention_logs.');
        return 0;
    }
}
