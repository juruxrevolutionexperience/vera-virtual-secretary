<?php

namespace App\Mail;

use App\Models\MeetingRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Seguimiento al cliente que recibió el enlace de reserva pero NO agendó en 24h.
 * Le recuerda con calidez que puede reservar cuando quiera (re-engancha el lead).
 */
class BookingFollowupMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public MeetingRequest $req,
        public string         $bookingUrl,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: '¿Reservamos tu cita?');
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.booking-followup',
            with: [
                'bookingUrl'   => $this->bookingUrl,
                'businessName' => $this->req->user?->veraConfig?->business_name ?? config('app.name'),
            ],
        );
    }
}
