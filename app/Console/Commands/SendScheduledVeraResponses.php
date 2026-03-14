<?php

namespace App\Console\Commands;

use App\Models\VeraEmailAction;
use App\Services\VeraResponseGenerator;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SendScheduledVeraResponses extends Command
{
    protected $signature = 'vera:send-scheduled';
    protected $description = 'Envía las respuestas de Vera que ya han cumplido su tiempo de espera';

    public function handle(): void
    {
        $pending = VeraEmailAction::where('action_type', 'replied')
            ->where('user_feedback', 'approved')
            ->where('response_sent', false)
            ->whereNotNull('scheduled_send_at')
            ->where('scheduled_send_at', '<=', now())
            ->with('email')
            ->get();

        if ($pending->isEmpty()) {
            return;
        }

        $this->info("Procesando {$pending->count()} respuestas programadas...");
        $generator = new VeraResponseGenerator();

        foreach ($pending as $action) {
            try {
                $generator->sendApprovedResponse($action, $action->email);
                Log::info('⏰ Respuesta programada enviada', [
                    'action_id' => $action->id,
                    'email_id'  => $action->email_id,
                ]);
                $this->line(" → Email #{$action->email_id} enviado");
            } catch (\Exception $e) {
                Log::error('❌ Error enviando respuesta programada', [
                    'action_id' => $action->id,
                    'error'     => $e->getMessage(),
                ]);
                $this->error(" → Error en #{$action->email_id}: " . $e->getMessage());
            }
        }
    }
}
