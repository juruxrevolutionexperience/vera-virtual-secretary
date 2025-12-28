<?php

namespace App\Mail;

use App\Models\VeraNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class VeraNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public VeraNotification $notification;

    public function __construct(VeraNotification $notification)
    {
        $this->notification = $notification;
    }

    public function build()
    {
        return $this->subject($this->notification->title)
                    ->text('emails.vera-notification-text')
                    ->with([
                        'notification' => $this->notification,
                    ]);
    }
}
