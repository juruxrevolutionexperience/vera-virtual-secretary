<?php

namespace App\Listeners;

use App\Events\VeraTokensConsumed;
use App\Models\VeraTokenUsage;
use Illuminate\Support\Facades\Log;
use Illuminate\Contracts\Queue\ShouldQueue;

class RecordTokenUsage implements ShouldQueue
{
    /**
     * Handle the event.
     */
    public function handle(VeraTokensConsumed $event): void
    {
        try {
            VeraTokenUsage::record(
                userId: $event->userId,
                actionType: $event->actionType,
                tokenData: $event->tokenData,
                emailId: $event->emailId,
                metadata: $event->metadata
            );

            Log::info('📊 Tokens registrados', [
                'user_id' => $event->userId,
                'action_type' => $event->actionType,
                'total_tokens' => $event->tokenData['total_tokens'] ?? 0,
                'email_id' => $event->emailId,
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Error registrando tokens', [
                'user_id' => $event->userId,
                'action_type' => $event->actionType,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
