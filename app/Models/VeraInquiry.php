<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Consulta que Vera no supo responder — tarjeta gestionable de la Bandeja.
 * El dueño la responde vía Vera (o la descarta) y puede guardarla como FAQ
 * para que la próxima vez Vera conteste sola.
 */
class VeraInquiry extends Model
{
    protected $fillable = [
        'user_id',
        'contact_id',
        'contact_identifier',
        'contact_name',
        'channel',
        'question',
        'answer',
        'status',
        'meeting_request_id',
        'answered_at',
    ];

    protected $casts = [
        'answered_at' => 'datetime',
    ];

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }
}
