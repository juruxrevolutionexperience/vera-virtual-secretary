<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WritingStylePattern extends Model
{
    protected $fillable = [
        'user_id',
        'common_greetings', 'common_closings',
        'signature_phrases', 'common_words',
        'overall_tone', 'formality_level',
        'uses_emojis', 'emoji_frequency',
        'avg_paragraph_length', 'avg_sentence_length', 'avg_email_length',
        'analyzed_emails_count', 'last_analysis_at'
    ];

    protected $casts = [
        'common_greetings' => 'array',
        'common_closings' => 'array',
        'signature_phrases' => 'array',
        'common_words' => 'array',
        'uses_emojis' => 'boolean',
        'formality_level' => 'decimal:2',
        'emoji_frequency' => 'decimal:2',
        'last_analysis_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
