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
        'cancel_token',
        'cancel_token_expires_at',
    ];

    protected $casts = [
        'context' => 'array',
        'booking_token_expires_at' => 'datetime',
        'booking_token_used_at'    => 'datetime',
        'booking_link_sent_at'     => 'datetime',
        'follow_up_sent_at'        => 'datetime',
        'cancel_token_expires_at'  => 'datetime',
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

    /**
     * Genera (o regenera) el token de la página de cancelación, válido X días.
     * No es de un solo uso: el cliente puede tener varias citas y volver al enlace.
     */
    public function generateCancelToken(int $validDays = 7): string
    {
        $token = bin2hex(random_bytes(20)); // 40 chars, URL-safe
        $this->update([
            'cancel_token'            => $token,
            'cancel_token_expires_at' => now()->addDays($validDays),
        ]);
        return $token;
    }

    /** El token de cancelación es válido si existe y no ha caducado. */
    public function cancelTokenIsValid(): bool
    {
        return $this->cancel_token
            && $this->cancel_token_expires_at
            && $this->cancel_token_expires_at->isFuture();
    }

    /**
     * Citas futuras (confirmadas/pendientes) de ESTE contacto, sea cual sea el canal.
     *
     * Única fuente de verdad para "¿qué citas puede cancelar este cliente?" — la usan
     * la página /cancelar y el flujo de cancelación de ProcessIncomingMessage. Casa por:
     * 1. La cadena de conversaciones del contacto (meeting_request_id): cubre las citas
     *    reservadas desde la web de reservas, que no guardan email ni teléfono.
     * 2. El identificador según canal: email (en sus variantes "Nombre <email>") o
     *    teléfono/chat-id en canales de mensajería.
     * A propósito NO casa por contact_name: dos clientes distintos pueden llamarse igual
     * y uno vería (y podría cancelar) las citas del otro.
     */
    public function upcomingContactMeetings()
    {
        $raw   = (string) $this->contact_identifier;
        $email = preg_match('/<(.+?)>/', $raw, $m)
            ? strtolower(trim($m[1]))
            : strtolower(trim($raw));

        $requestIds = self::where('user_id', $this->user_id)
            ->where('channel', $this->channel)
            ->where('contact_identifier', $this->contact_identifier)
            ->pluck('id');

        return Meeting::where('user_id', $this->user_id)
            ->whereIn('status', ['confirmed', 'pending'])
            ->where('scheduled_at', '>', now())
            ->where(function ($q) use ($raw, $email, $requestIds) {
                $q->whereIn('meeting_request_id', $requestIds);
                if ($this->channel === 'email') {
                    $q->orWhere('contact_email', $raw)
                      ->orWhereRaw('LOWER(contact_email) = ?', [$email])
                      ->orWhere('contact_email', 'like', '%<' . $email . '>%');
                } else {
                    $q->orWhere('contact_phone', $raw);
                }
            })
            ->orderBy('scheduled_at');
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
