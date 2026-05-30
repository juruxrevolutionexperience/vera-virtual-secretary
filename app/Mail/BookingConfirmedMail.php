<?php

namespace App\Mail;

use App\Models\Meeting;
use App\Models\MeetingRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BookingConfirmedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Meeting        $meeting,
        public MeetingRequest $req,
        public string         $dateLabel,
        public bool           $autoConfirmed,
    ) {}

    public function envelope(): Envelope
    {
        $subject = $this->autoConfirmed
            ? "✅ Cita confirmada — {$this->dateLabel}"
            : "📅 Solicitud recibida — pendiente de confirmación";

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.booking-confirmed',
            with: [
                'meeting'       => $this->meeting,
                'req'           => $this->req,
                'dateLabel'     => $this->dateLabel,
                'autoConfirmed' => $this->autoConfirmed,
                'businessName'  => $this->req->user->veraConfig?->business_name ?? config('app.name'),
            ],
        );
    }
}
