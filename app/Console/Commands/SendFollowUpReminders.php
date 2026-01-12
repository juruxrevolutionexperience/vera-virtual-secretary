<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\FollowUpService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SendFollowUpReminders extends Command
{
    protected $signature = 'emails:send-follow-up-reminders
                           {--user= : ID del usuario específico (opcional)}
                           {--dry-run : Modo simulación sin enviar recordatorios}';

    protected $description = 'Enviar recordatorios de seguimiento que están listos';

    protected FollowUpService $followUpService;

    public function __construct(FollowUpService $followUpService)
    {
        parent::__construct();
        $this->followUpService = $followUpService;
    }

    public function handle(): int
    {
        $this->info('📬 Enviando recordatorios de seguimiento...');

        if ($this->option('dry-run')) {
            $this->warn('⚠️  Modo DRY-RUN activado - No se enviarán recordatorios realmente');
            return Command::SUCCESS;
        }

        try {
            $user = $this->option('user') ? User::find($this->option('user')) : null;

            $stats = $this->followUpService->processReadyReminders($user);

            $this->info('✅ Recordatorios procesados');
            $this->newLine();
            $this->table(
                ['Métrica', 'Cantidad'],
                [
                    ['Recordatorios procesados', $stats['reminders_processed']],
                    ['Notificaciones enviadas', $stats['notifications_sent']],
                    ['Errores', $stats['errors']],
                ]
            );

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('❌ Error: ' . $e->getMessage());
            Log::error('Error in SendFollowUpReminders command', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return Command::FAILURE;
        }
    }
}
