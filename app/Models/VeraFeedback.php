<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VeraFeedback extends Model
{
    protected $table = 'vera_feedbacks';

    protected $fillable = [
        'user_id',
        'category',
        'message',
        'context_url',
        'conversation_id',
        'metadata',
        'status',
        'admin_notes',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public const CATEGORIES = [
        'respuesta_mala'   => 'Respuesta mala',
        'respuesta_buena'  => 'Respuesta buena',
        'bug_web'          => 'Bug del panel web',
        'bug_calendar'     => 'Bug de calendario',
        'sugerencia'       => 'Sugerencia',
        'otro'             => 'Otro',
    ];

    public const STATUSES = [
        'nuevo'      => 'Nuevo',
        'revisado'   => 'Revisado',
        'resuelto'   => 'Resuelto',
        'descartado' => 'Descartado',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(VeraConversation::class, 'conversation_id');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(VeraFeedbackAttachment::class);
    }

    public function categoryLabel(): string
    {
        return self::CATEGORIES[$this->category] ?? $this->category;
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }
}
