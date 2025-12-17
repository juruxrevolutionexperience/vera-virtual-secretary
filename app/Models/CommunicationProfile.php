<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommunicationProfile extends Model
{
    protected $fillable = [
        'user_id', 'email_address', 'name',
        'total_emails_received', 'total_emails_sent',
        'avg_response_time_hours', 'communication_frequency',
        'detected_tone', 'formality_score', 'avg_sentiment',
        'common_topics', 'typical_greetings', 'typical_closings',
        'avg_message_length', 'last_interaction_at', 'profile_updated_at'
    ];

    protected $casts = [
        'common_topics' => 'array',
        'typical_greetings' => 'array',
        'typical_closings' => 'array',
        'last_interaction_at' => 'datetime',
        'profile_updated_at' => 'datetime',
        'formality_score' => 'decimal:2',
        'avg_sentiment' => 'decimal:2',
        'avg_response_time_hours' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
