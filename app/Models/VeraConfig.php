<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VeraConfig extends Model
{
    protected $fillable = [
        'user_id',
        'main_objective',
        'writing_style',
        'auto_response_enabled',
        'auto_response_mode',
        'auto_response_require_approval',
        'confidence_threshold',
        'newsletter_handling',
        'important_contacts',
        'availability_start',
        'availability_end',
        'autonomy_level',
        're_analysis_frequency_days',
        'last_analysis_at',
        'last_re_analysis_at',
        'analysis_summary',
        'onboarding_completed',
        'onboarding_completed_at',
    ];

    protected $casts = [
        'writing_style' => 'array',
        'important_contacts' => 'array',
        'analysis_summary' => 'array',
        'auto_response_enabled' => 'boolean',
        'auto_response_require_approval' => 'boolean',
        'onboarding_completed' => 'boolean',
        'confidence_threshold' => 'decimal:2',
        'last_analysis_at' => 'datetime',
        'last_re_analysis_at' => 'datetime',
        'onboarding_completed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if re-analysis is needed
     */
    public function needsReAnalysis(): bool
    {
        if (!$this->last_re_analysis_at) {
            return true;
        }

        $daysSinceLastAnalysis = now()->diffInDays($this->last_re_analysis_at);

        return $daysSinceLastAnalysis >= $this->re_analysis_frequency_days;
    }

    /**
     * Check if response should be sent based on availability hours
     */
    public function isWithinAvailabilityHours(): bool
    {
        $now = now()->format('H:i');

        return $now >= $this->availability_start && $now <= $this->availability_end;
    }
}
