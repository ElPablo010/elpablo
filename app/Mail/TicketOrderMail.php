<?php

namespace App\Mail;

use App\Models\TicketOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Bevestigingsmail naar de koper: overzicht van de bestelling + één PDF met
 * QR-code per ticket in bijlage. Wordt gerenderd in de taal van de bestelling
 * (de job zet de locale vóór het versturen).
 */
class TicketOrderMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public TicketOrder $order)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('Je tickets voor :event', ['event' => $this->order->event->translated('name', $this->order->locale)]),
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.ticket-order');
    }

    /**
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        return $this->order->tickets
            ->filter(fn ($ticket) => filled($ticket->pdf_path))
            ->map(fn ($ticket) => Attachment::fromStorageDisk('local', $ticket->pdf_path)
                ->as('ticket-'.$ticket->token.'.pdf')
                ->withMime('application/pdf'))
            ->values()
            ->all();
    }
}
