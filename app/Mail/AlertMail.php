<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** Plain-text alert email. */
class AlertMail extends Mailable
{
    public function __construct(public readonly string $title, public readonly string $text)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: '[Fluxmon] '.$this->title);
    }

    public function content(): Content
    {
        return new Content(text: 'mail.alert');
    }
}
