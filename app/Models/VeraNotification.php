<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VeraNotification extends Model
{
    protected $fillable = [
        'user_id',
        'type',
        'title',
        'message',
        'data',
        'channel',
        'status',
        'scheduled_for',
        'sent_at',
        'read_at',
    ];

    protected $casts = [
        'data' => 'array',
        'scheduled_for' => 'datetime',
        'sent_at' => 'datetime',
        'read_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeScheduledNow($query)
    {
        return $query->where('scheduled_for', '<=', now());
    }

    public function markAsSent(): void
    {
        $this->update([
            'status' => 'sent',
            'sent_at' => now(),
        ]);
    }

    public function markAsRead(): void
    {
        $this->update(['read_at' => now()]);
    }
}
