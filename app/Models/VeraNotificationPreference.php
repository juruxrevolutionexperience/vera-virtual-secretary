<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VeraNotificationPreference extends Model
{
    protected $fillable = [
        'user_id',
        'email_enabled',
        'push_enabled',
        'daily_summary_enabled',
        'daily_summary_time',
        'weekly_summary_enabled',
        'weekly_summary_day',
        'weekly_summary_time',
        'monthly_summary_enabled',
        'monthly_summary_day',
        'monthly_summary_time',
        'limit_warnings_enabled',
        'warning_threshold_percentage',
        'high_activity_alerts',
    ];

    protected $casts = [
        'email_enabled' => 'boolean',
        'push_enabled' => 'boolean',
        'daily_summary_enabled' => 'boolean',
        'weekly_summary_enabled' => 'boolean',
        'monthly_summary_enabled' => 'boolean',
        'limit_warnings_enabled' => 'boolean',
        'high_activity_alerts' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function getOrCreateForUser(User $user): self
    {
        return self::firstOrCreate(['user_id' => $user->id]);
    }
}
