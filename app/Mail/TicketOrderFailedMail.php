<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Alarmmail naar de beheerder wanneer een betaalde bestelling niet verwerkt
 * raakt (webhook-fulfilment gooit). Stripe blijft retryen; deze mail zorgt dat
 * er intussen een mens meekijkt.
 */
class TicketOrderFailedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $uuid,
        public string $error,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Ticketbestelling niet verwerkt — actie nodig',
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.ticket-order-failed');
    }
}
