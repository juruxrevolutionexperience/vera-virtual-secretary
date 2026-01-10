<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class AutoResponder extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'email_account_id',
        'name',
        'type',
        'email_template_id',
        'subject',
        'body',
        'is_active',
        'active_from',
        'active_until',
        'sender_filters',
        'subject_filters',
        'only_first_contact',
        'max_responses_per_sender',
        'max_responses_per_day',
        'delay_minutes',
        'schedule',
        'reply_to_all',
        'skip_auto_replies',
        'responses_sent',
        'last_sent_at',
    ];

    protected $casts = [
        'sender_filters' => 'array',
        'subject_filters' => 'array',
        'schedule' => 'array',
        'is_active' => 'boolean',
        'active_from' => 'datetime',
        'active_until' => 'datetime',
        'only_first_contact' => 'boolean',
        'reply_to_all' => 'boolean',
        'skip_auto_replies' => 'boolean',
        'max_responses_per_sender' => 'integer',
        'max_responses_per_day' => 'integer',
        'delay_minutes' => 'integer',
        'responses_sent' => 'integer',
        'last_sent_at' => 'datetime',
    ];

    /**
     * Usuario propietario del auto-responder
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Cuenta de email asociada
     */
    public function emailAccount(): BelongsTo
    {
        return $this->belongsTo(EmailAccount::class);
    }

    /**
     * Template utilizado
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(EmailTemplate::class, 'email_template_id');
    }

    /**
     * Historial de respuestas enviadas
     */
    public function history(): HasMany
    {
        return $this->hasMany(AutoResponderHistory::class);
    }

    /**
     * Scope: Solo auto-responders activos
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('active_from')
                    ->orWhere('active_from', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('active_until')
                    ->orWhere('active_until', '>=', now());
            });
    }

    /**
     * Scope: Por tipo
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope: Por usuario
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Verificar si el auto-responder está actualmente activo
     */
    public function isCurrentlyActive(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        $now = now();

        if ($this->active_from && $now < $this->active_from) {
            return false;
        }

        if ($this->active_until && $now > $this->active_until) {
            return false;
        }

        return true;
    }

    /**
     * Verificar si debe responder a un email específico
     */
    public function shouldRespondTo(Email $email): bool
    {
        // Verificar si está activo
        if (!$this->isCurrentlyActive()) {
            return false;
        }

        // Verificar si ya respondió a este sender
        if ($this->only_first_contact && $this->hasRespondedTo($email->from_email)) {
            return false;
        }

        // Verificar límite de respuestas por sender
        if ($this->max_responses_per_sender) {
            $count = $this->history()
                ->where('sender_email', $email->from_email)
                ->count();

            if ($count >= $this->max_responses_per_sender) {
                return false;
            }
        }

        // Verificar límite diario
        if ($this->max_responses_per_day) {
            $todayCount = $this->history()
                ->whereDate('responded_at', today())
                ->count();

            if ($todayCount >= $this->max_responses_per_day) {
                return false;
            }
        }

        // Verificar filtros de sender
        if ($this->sender_filters && count($this->sender_filters) > 0) {
            $matched = false;
            foreach ($this->sender_filters as $filter) {
                if (str_contains($email->from_email, $filter)) {
                    $matched = true;
                    break;
                }
            }
            if (!$matched) {
                return false;
            }
        }

        // Verificar filtros de asunto
        if ($this->subject_filters && count($this->subject_filters) > 0) {
            $matched = false;
            foreach ($this->subject_filters as $filter) {
                if (stripos($email->subject, $filter) !== false) {
                    $matched = true;
                    break;
                }
            }
            if (!$matched) {
                return false;
            }
        }

        // Verificar si es un auto-reply y debemos saltarlo
        if ($this->skip_auto_replies && $this->isAutoReply($email)) {
            return false;
        }

        // Verificar horario de operación
        if ($this->schedule && !$this->isWithinSchedule()) {
            return false;
        }

        return true;
    }

    /**
     * Verificar si ya respondió a un email específico
     */
    public function hasRespondedTo(string $senderEmail): bool
    {
        return $this->history()
            ->where('sender_email', $senderEmail)
            ->exists();
    }

    /**
     * Verificar si un email es un auto-reply
     */
    protected function isAutoReply(Email $email): bool
    {
        $autoReplyHeaders = [
            'Auto-Submitted' => 'auto-replied',
            'X-Auto-Response-Suppress' => 'All',
            'Precedence' => 'auto_reply',
        ];

        // Simplificación: verificar por asunto
        $autoReplySubjects = ['Out of Office', 'Automatic reply', 'Auto:', 'Fuera de la oficina'];

        foreach ($autoReplySubjects as $subject) {
            if (stripos($email->subject, $subject) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Verificar si estamos dentro del horario programado
     */
    protected function isWithinSchedule(): bool
    {
        if (!$this->schedule) {
            return true; // Sin horario = siempre activo
        }

        $now = now();
        $dayOfWeek = strtolower($now->format('l')); // monday, tuesday, etc.

        if (!isset($this->schedule[$dayOfWeek]) || !$this->schedule[$dayOfWeek]['enabled']) {
            return false;
        }

        $schedule = $this->schedule[$dayOfWeek];
        $currentTime = $now->format('H:i');

        return $currentTime >= $schedule['start'] && $currentTime <= $schedule['end'];
    }

    /**
     * Registrar respuesta enviada
     */
    public function recordResponse(Email $email): void
    {
        AutoResponderHistory::create([
            'auto_responder_id' => $this->id,
            'email_id' => $email->id,
            'sender_email' => $email->from_email,
            'responded_at' => now(),
        ]);

        $this->increment('responses_sent');
        $this->update(['last_sent_at' => now()]);
    }

    /**
     * Obtener el contenido de la respuesta (desde template o directo)
     */
    public function getResponseContent(Email $email): array
    {
        if ($this->email_template_id && $this->template) {
            // Usar template con datos del email
            return $this->template->render([
                'nombre' => $email->from_name ?? 'Usuario',
                'email' => $email->from_email,
                'asunto' => $email->subject,
                'fecha' => now()->format('d/m/Y'),
            ]);
        }

        // Usar contenido directo
        return [
            'subject' => $this->subject ?? 'Re: ' . $email->subject,
            'body' => $this->body ?? '',
        ];
    }
}
