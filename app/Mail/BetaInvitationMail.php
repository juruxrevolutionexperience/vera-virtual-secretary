<?php

namespace App\Mail;

use App\Models\Invitation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BetaInvitationMail extends Mailable
{
    use Queueable, SerializesModels;

    public Invitation $invitation;
    public string $inviteUrl;

    public function __construct(Invitation $invitation)
    {
        $this->invitation = $invitation;
        $this->inviteUrl  = config('app.url') . '/invite/' . $invitation->token;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Te han invitado a probar Vera (beta)',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.beta-invitation',
            with: [
                'invitation' => $this->invitation,
                'inviteUrl'  => $this->inviteUrl,
            ],
        );
    }
}
