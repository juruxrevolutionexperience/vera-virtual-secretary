<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class VeraTokensConsumed
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public int $userId;
    public string $actionType;
    public array $tokenData;
    public ?int $emailId;
    public array $metadata;

    /**
     * Create a new event instance.
     *
     * @param int $userId
     * @param string $actionType ('email_analysis', 'response_generation', 'chat_interaction')
     * @param array $tokenData ['prompt_tokens', 'completion_tokens', 'total_tokens', 'model']
     * @param int|null $emailId
     * @param array $metadata Datos adicionales (subject, from, content_length, etc.)
     */
    public function __construct(
        int $userId,
        string $actionType,
        array $tokenData,
        ?int $emailId = null,
        array $metadata = []
    ) {
        $this->userId = $userId;
        $this->actionType = $actionType;
        $this->tokenData = $tokenData;
        $this->emailId = $emailId;
        $this->metadata = $metadata;
    }
}
