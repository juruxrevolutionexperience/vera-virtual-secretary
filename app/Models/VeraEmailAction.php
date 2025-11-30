<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VeraEmailAction extends Model
{
    use HasFactory;

    protected $fillable = [
        'email_id', 'user_id', 'action_type', 'action_description',
        'action_data', 'reasoning', 'generated_response', 'response_sent',
        'response_sent_at', 'user_feedback', 'user_notes', 'confidence_score',
        'used_for_training', 'ml_features',
    ];

    protected $casts = [
        'action_data' => 'array',
        'response_sent' => 'boolean',
        'used_for_training' => 'boolean',
        'ml_features' => 'array',
        'response_sent_at' => 'datetime',
        'confidence_score' => 'decimal:2',
    ];

    public function email(): BelongsTo
    {
        return $this->belongsTo(Email::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('action_type', $type);
    }

    public function scopeByFeedback($query, $feedback)
    {
        return $query->where('user_feedback', $feedback);
    }

    public function scopePending($query)
    {
        return $query->where('user_feedback', 'pending');
    }

    public function approve(): void
    {
        $this->update(['user_feedback' => 'approved']);
    }

    public function reject(): void
    {
        $this->update(['user_feedback' => 'rejected']);
    }
}
