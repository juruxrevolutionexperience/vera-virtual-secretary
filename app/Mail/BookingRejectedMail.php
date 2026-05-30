<?php

namespace App\Mail;

use App\Models\Meeting;
use App\Models\MeetingRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BookingRejectedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Meeting        $meeting,
        public MeetingRequest $req,
        public string         $dateLabel,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: '❌ Cita no disponible — ' . $this->dateLabel);
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.booking-rejected',
            with: [
                'meeting'      => $this->meeting,
                'req'          => $this->req,
                'dateLabel'    => $this->dateLabel,
                'businessName' => $this->req->user->veraConfig?->business_name ?? config('app.name'),
            ],
        );
    }
}
