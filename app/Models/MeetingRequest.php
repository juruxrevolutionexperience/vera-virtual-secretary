<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class MeetingRequest extends Model
{
    protected $fillable = [
        'user_id',
        'channel',
        'contact_id',
        'contact_identifier',
        'state',
        'context',
        'conversation_id',
        'meeting_id',
        'escalation_reason',
    ];

    protected $casts = [
        'context' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(VeraConversation::class);
    }

    public function meeting(): HasOne
    {
        return $this->hasOne(Meeting::class, 'meeting_request_id');
    }

    public function scopeActive($query)
    {
        return $query->whereIn('state', ['new', 'negotiating', 'awaiting']);
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }
}
