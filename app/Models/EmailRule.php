<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'email_category_id', 'name', 'description',
        'is_active', 'priority', 'conditions', 'condition_match',
        'actions', 'times_applied', 'last_applied_at',
        'is_ai_generated', 'ai_confidence',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'conditions' => 'array',
        'actions' => 'array',
        'is_ai_generated' => 'boolean',
        'last_applied_at' => 'datetime',
        'ai_confidence' => 'decimal:2',
        'priority' => 'integer',
        'times_applied' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(EmailCategory::class, 'email_category_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByPriority($query)
    {
        return $query->orderBy('priority', 'desc');
    }

    public function scopeAiGenerated($query)
    {
        return $query->where('is_ai_generated', true);
    }

    public function incrementApplied(): void
    {
        $this->increment('times_applied');
        $this->update(['last_applied_at' => now()]);
    }

    public function matchesEmail(Email $email): bool
    {
        $conditionsMet = [];

        foreach ($this->conditions as $key => $value) {
            $conditionsMet[] = match($key) {
                'from' => str_contains(strtolower($email->from_email), strtolower($value)),
                'subject_contains' => str_contains(strtolower($email->subject), strtolower($value)),
                'has_attachments' => $email->has_attachments == $value,
                'priority' => $email->priority == $value,
                default => false,
            };
        }

        return $this->condition_match === 'all'
            ? !in_array(false, $conditionsMet)
            : in_array(true, $conditionsMet);
    }
}
