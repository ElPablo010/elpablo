<?php

namespace App\Jobs;

use App\Enums\OrderStatus;
use App\Mail\TicketOrderMail;
use App\Models\TicketOrder;
use App\Services\EventTicketPdf;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;

/**
 * Stuurt de bevestigingsmail met de ticket-PDF's (QR-codes) naar de koper, in
 * de taal van de bestelling. Ontdubbelt zichzelf per bestelling (7 dagen) —
 * dubbele webhook-delivery of de bedankpagina-fallback triggeren de mail dus
 * nooit twee keer. `$force` (admin: "tickets opnieuw verzenden") omzeilt dat.
 */
class SendTicketOrderEmailJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 120;

    public function __construct(
        public int $orderId,
        public bool $force = false,
    ) {
    }

    public function handle(EventTicketPdf $pdf): void
    {
        $cacheKey = 'ticket_order_email_sent_'.$this->orderId;
        if (! $this->force && Cache::has($cacheKey)) {
            return;
        }

        $order = TicketOrder::query()
            ->with(['event', 'items', 'tickets.ticketType'])
            ->find($this->orderId);

        if (! $order || $order->status !== OrderStatus::Paid) {
            return;
        }

        // De queue-worker heeft geen request-context: zet de taal van de
        // bestelling expliciet zodat de mail (en de PDF's) meertalig kloppen.
        $original = app()->getLocale();
        app()->setLocale($order->locale);

        try {
            foreach ($order->tickets as $ticket) {
                $pdf->generate($ticket);
            }

            Mail::to($order->buyer_email)->send(new TicketOrderMail($order));

            Cache::put($cacheKey, true, now()->addDays(7));
        } finally {
            app()->setLocale($original);
        }
    }
}
