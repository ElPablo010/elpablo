<?php

namespace App\Mail;

use App\Models\TicketOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Korte melding naar de beheerder bij elke betaalde bestelling. Gaat via de
 * queue zodat de webhook er nooit op wacht, en wordt enkel verstuurd bij de
 * overgang naar "betaald" (zie TicketOrderFulfillment), dus nooit dubbel.
 */
class TicketOrderPaidAdminMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public TicketOrder $order)
    {
    }

    public function envelope(): Envelope
    {
        $tickets = $this->order->items->sum('quantity');

        return new Envelope(
            subject: "Nieuwe bestelling: {$tickets} ticket(s) voor {$this->order->event->name}",
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.ticket-order-paid-admin');
    }
}
