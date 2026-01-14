<?php

namespace App\Console\Commands\Security;

use Illuminate\Console\Command;

class CleanExpiredSessions extends Command
{
    protected $signature = 'security:clean-sessions';
    protected $description = 'Limpiar sesiones expiradas';

    public function handle()
    {
        $count = \App\Models\UserSession::cleanExpired();
        $this->info("Limpiadas {$count} sesiones expiradas.");
        return 0;
    }
}
