<?php

namespace App\Listeners;

use App\Events\VeraTokensConsumed;
use App\Models\VeraTokenUsage;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Contracts\Queue\ShouldQueue;

class RecordTokenUsage implements ShouldQueue
{
    /**
     * Handle the event.
     * 1. Guarda el log en vera_token_usage
     * 2. Descuenta tokens del saldo mensual del usuario
     */
    public function handle(VeraTokensConsumed $event): void
    {
        try {
            $totalTokens = $event->tokenData['total_tokens'] ?? 0;

            // 1. Guardar log de uso
            VeraTokenUsage::record(
                userId: $event->userId,
                actionType: $event->actionType,
                tokenData: $event->tokenData,
                emailId: $event->emailId,
                metadata: $event->metadata
            );

            // 2. Descontar del saldo mensual del usuario
            $user = User::find($event->userId);
            if ($user && $totalTokens > 0) {
                $user->consumeTokens($totalTokens);
            }

            Log::info('📊 Tokens registrados y descontados', [
                'user_id'      => $event->userId,
                'action_type'  => $event->actionType,
                'total_tokens' => $totalTokens,
                'email_id'     => $event->emailId,
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Error registrando tokens', [
                'user_id'     => $event->userId,
                'action_type' => $event->actionType,
                'error'       => $e->getMessage(),
            ]);
        }
    }
}
