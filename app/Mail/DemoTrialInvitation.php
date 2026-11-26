<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DemoTrialInvitation extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $recipientName,
        public int $days,
        public string $url,
        public string $endsOn,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('Your Jumuika Pass trial is ready'),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.demo-trial',
        );
    }
}
