<?php

namespace App\Mail;

use App\Models\Meeting;
use App\Models\VeraConfig;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BookingReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param string $kind '24h' (recordatorio del día antes) o '1h' (en una hora)
     */
    public function __construct(
        public Meeting $meeting,
        public string  $kind = '24h',
    ) {}

    public function envelope(): Envelope
    {
        $cuando = $this->kind === '1h' ? 'en 1 hora' : 'mañana';
        return new Envelope(subject: "Recordatorio: tu cita {$cuando}");
    }

    public function content(): Content
    {
        $tz   = $this->meeting->timezone ?: config('app.timezone', 'Europe/Madrid');
        $when = $this->meeting->scheduled_at->copy()->setTimezone($tz);

        return new Content(
            view: 'emails.booking-reminder',
            with: [
                'meeting'       => $this->meeting,
                'kind'          => $this->kind,
                'dateLabel'     => $when->locale('es')->translatedFormat('l d \d\e F \a \l\a\s H:i')
                                    . ' (' . VeraConfig::humanTimezone($tz) . ')',
                'businessName'  => $this->meeting->user?->veraConfig?->business_name ?? config('app.name'),
            ],
        );
    }
}
