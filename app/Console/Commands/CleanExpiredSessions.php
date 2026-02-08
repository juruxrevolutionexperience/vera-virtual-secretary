<?php

namespace App\Console\Commands;

use App\Models\UserSession;
use Illuminate\Console\Command;

class CleanExpiredSessions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sessions:clean-expired';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Limpiar sesiones expiradas del sistema (más de 7 días)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🧹 Limpiando sesiones expiradas...');

        $deleted = UserSession::cleanExpired();

        if ($deleted > 0) {
            $this->info("✅ Se eliminaron {$deleted} sesiones expiradas.");
        } else {
            $this->info('✅ No hay sesiones expiradas para limpiar.');
        }

        return Command::SUCCESS;
    }
}
