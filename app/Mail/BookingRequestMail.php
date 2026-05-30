<?php

namespace App\Mail;

use App\Models\Meeting;
use App\Models\MeetingRequest;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BookingRequestMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User           $owner,
        public Meeting        $meeting,
        public MeetingRequest $req,
        public string         $dateLabel,
    ) {}

    public function envelope(): Envelope
    {
        $name = $this->meeting->contact_name ?? $this->req->contact_identifier;
        return new Envelope(
            subject: "Nueva reserva de {$name} — pendiente de confirmar",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.booking-request',
            with: [
                'owner'      => $this->owner,
                'meeting'    => $this->meeting,
                'req'        => $this->req,
                'dateLabel'  => $this->dateLabel,
                'panelUrl'   => config('app.url') . '/dashboard',
            ],
        );
    }
}
