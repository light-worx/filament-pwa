<?php

namespace Lightworx\FilamentPwa\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EmailVerificationPin extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $pin,
        public readonly string $appName,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Your {$this->appName} verification code: {$this->pin}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'pwa::emails.verification-pin',
        );
    }
}