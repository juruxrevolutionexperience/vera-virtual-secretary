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
        'response_allowed_categories',
        'response_blocked_categories',
        'blocked_keywords',
        'blocked_senders',
        'allowed_senders',
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
        'response_allowed_categories' => 'array',
        'response_blocked_categories' => 'array',
        'blocked_keywords' => 'array',
        'blocked_senders' => 'array',
        'allowed_senders' => 'array',
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

    /**
     * Check if email category is allowed for auto-response
     */
    public function isCategoryAllowed(?string $category): bool
    {
        // Si no hay categoría, permitir por defecto
        if (!$category) {
            return true;
        }

        // Si hay lista de permitidas, verificar que esté en la lista
        if (!empty($this->response_allowed_categories)) {
            return in_array($category, $this->response_allowed_categories);
        }

        // Si hay lista de bloqueadas, verificar que NO esté en la lista
        if (!empty($this->response_blocked_categories)) {
            return !in_array($category, $this->response_blocked_categories);
        }

        // Por defecto, permitir todo
        return true;
    }

    /**
     * Check if sender is blocked
     */
    public function isSenderBlocked(string $senderEmail): bool
    {
        if (empty($this->blocked_senders)) {
            return false;
        }

        $senderEmail = strtolower($senderEmail);

        foreach ($this->blocked_senders as $pattern) {
            $pattern = strtolower($pattern);

            // Patrón exacto
            if ($senderEmail === $pattern) {
                return true;
            }

            // Patrón con @ al inicio (ej: @spam.com)
            if (str_starts_with($pattern, '@') && str_ends_with($senderEmail, $pattern)) {
                return true;
            }

            // Patrón como substring (ej: "noreply")
            if (str_contains($senderEmail, $pattern)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if sender is in allowed list (priority senders)
     */
    public function isSenderAllowed(string $senderEmail): bool
    {
        if (empty($this->allowed_senders)) {
            return false;
        }

        $senderEmail = strtolower($senderEmail);

        foreach ($this->allowed_senders as $pattern) {
            $pattern = strtolower($pattern);

            // Patrón exacto
            if ($senderEmail === $pattern) {
                return true;
            }

            // Patrón con @ al inicio (ej: @clientevip.com)
            if (str_starts_with($pattern, '@') && str_ends_with($senderEmail, $pattern)) {
                return true;
            }

            // Patrón como substring
            if (str_contains($senderEmail, $pattern)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if email contains blocked keywords
     */
    public function hasBlockedKeywords(string $subject, string $body): bool
    {
        if (empty($this->blocked_keywords)) {
            return false;
        }

        $subject = strtolower($subject);
        $body = strtolower(strip_tags($body));

        foreach ($this->blocked_keywords as $keyword) {
            $keyword = strtolower($keyword);

            if (str_contains($subject, $keyword) || str_contains($body, $keyword)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get default filter configuration (for new users)
     */
    public static function getDefaultFilters(): array
    {
        return [
            'response_allowed_categories' => null, // null = todas permitidas
            'response_blocked_categories' => ['promociones', 'newsletters'], // Bloquear obvios
            'blocked_keywords' => ['unsubscribe', 'darse de baja', 'cancelar suscripción'],
            'blocked_senders' => ['noreply', 'no-reply', 'donotreply'],
            'allowed_senders' => [], // Vacío por defecto, el usuario agrega
        ];
    }
}
