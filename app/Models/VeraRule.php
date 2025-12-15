<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VeraRule extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'description',
        'enabled',
        'priority',
        'rule_type',
        'conditions',
        'action',
        'times_applied',
        'last_applied_at',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'priority' => 'integer',
        'conditions' => 'array',
        'action' => 'array',
        'times_applied' => 'integer',
        'last_applied_at' => 'datetime',
    ];

    /**
     * Relación con User
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope: Solo reglas habilitadas
     */
    public function scopeEnabled($query)
    {
        return $query->where('enabled', true);
    }

    /**
     * Scope: Ordenadas por prioridad
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('priority', 'desc');
    }

    /**
     * Incrementar contador de veces aplicada
     */
    public function incrementApplied(): void
    {
        $this->increment('times_applied');
        $this->update(['last_applied_at' => now()]);
    }

    /**
     * Verificar si una regla aplica a un email
     */
    public function matches(Email $email): bool
    {
        foreach ($this->conditions as $field => $value) {
            if (!$this->matchesCondition($email, $field, $value)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Verificar si una condición específica se cumple
     */
    protected function matchesCondition(Email $email, string $field, mixed $value): bool
    {
        return match($field) {
            'from_email' => $email->from_email === $value,
            'from_email_contains' => str_contains($email->from_email, $value),
            'from_email_domain' => str_ends_with($email->from_email, '@' . $value),
            'subject_contains' => str_contains(strtolower($email->subject), strtolower($value)),
            'subject_equals' => $email->subject === $value,
            'category' => $email->category === $value,
            'sentiment_below' => $email->vera_sentiment_score < $value,
            'sentiment_above' => $email->vera_sentiment_score > $value,
            'has_attachments' => $email->has_attachments === $value,
            default => false,
        };
    }
}
