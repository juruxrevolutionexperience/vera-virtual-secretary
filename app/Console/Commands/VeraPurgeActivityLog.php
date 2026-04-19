<?php

namespace App\Console\Commands;

use App\Models\VeraActivityLog;
use Illuminate\Console\Command;

class VeraPurgeActivityLog extends Command
{
    protected $signature = 'vera:purge-activity-log
                            {--days=90 : Eliminar registros más antiguos que N días (default: 90)}
                            {--dry-run : Mostrar cuántos se borrarían sin borrar nada}';

    protected $description = 'Elimina registros antiguos de vera_activity_log para controlar el crecimiento de la tabla';

    public function handle(): int
    {
        $days   = (int) $this->option('days');
        $dryRun = $this->option('dry-run');
        $cutoff = now()->subDays($days);

        $query = VeraActivityLog::where('created_at', '<', $cutoff);
        $count = $query->count();

        if ($count === 0) {
            $this->info("No hay registros anteriores a {$cutoff->toDateString()} que purgar.");
            return 0;
        }

        if ($dryRun) {
            $this->warn("[DRY-RUN] Se borrarían {$count} registros anteriores a {$cutoff->toDateString()}.");
            return 0;
        }

        // Borrar en lotes de 500 para no bloquear la tabla
        $deleted = 0;
        while (true) {
            $batch = VeraActivityLog::where('created_at', '<', $cutoff)->limit(500)->delete();
            $deleted += $batch;
            if ($batch === 0) break;
        }

        $this->info("✅ Purgados {$deleted} registros de vera_activity_log anteriores a {$cutoff->toDateString()}.");
        return 0;
    }
}
