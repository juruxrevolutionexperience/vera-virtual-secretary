<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class MeetingRequest extends Model
{
    protected $fillable = [
        'user_id',
        'channel',
        'contact_id',
        'contact_identifier',
        'state',
        'context',
        'conversation_id',
        'meeting_id',
        'escalation_reason',
        'booking_token',
        'booking_token_expires_at',
        'booking_token_used_at',
        'booking_link_sent_at',
        'follow_up_sent_at',
    ];

    protected $casts = [
        'context' => 'array',
        'booking_token_expires_at' => 'datetime',
        'booking_token_used_at'    => 'datetime',
        'booking_link_sent_at'     => 'datetime',
        'follow_up_sent_at'        => 'datetime',
    ];

    /**
     * Genera (o regenera) un token de reserva de un solo uso, válido X días.
     * Devuelve el token para construir el enlace público.
     */
    public function generateBookingToken(int $validDays = 7): string
    {
        $token = bin2hex(random_bytes(20)); // 40 chars, URL-safe
        $this->update([
            'booking_token'            => $token,
            'booking_token_expires_at' => now()->addDays($validDays),
            'booking_token_used_at'    => null,
        ]);
        return $token;
    }

    /**
     * El token es válido si existe, no ha expirado y no se ha usado todavía.
     */
    public function bookingTokenIsValid(): bool
    {
        return $this->booking_token
            && !$this->booking_token_used_at
            && $this->booking_token_expires_at
            && $this->booking_token_expires_at->isFuture();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(VeraConversation::class);
    }

    public function meeting(): HasOne
    {
        return $this->hasOne(Meeting::class, 'meeting_request_id');
    }

    public function scopeActive($query)
    {
        return $query->whereIn('state', ['new', 'negotiating', 'awaiting']);
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }
}
