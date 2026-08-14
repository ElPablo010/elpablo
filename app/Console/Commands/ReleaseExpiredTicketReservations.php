<?php

namespace App\Console\Commands;

use App\Enums\OrderStatus;
use App\Models\TicketOrder;
use Illuminate\Console\Command;

/**
 * Vangnet voor verlaten checkouts: bestellingen die hun reserveringsdeadline
 * (40 min) voorbij zijn zonder betaling geven hun tickets — en dus capaciteit —
 * weer vrij. Het checkout.session.expired-webhook-event doet dit meestal al
 * eerder; dit command vangt gemiste webhooks op. Draait elke 5 minuten.
 */
class ReleaseExpiredTicketReservations extends Command
{
    protected $signature = 'events:release-expired-reservations';

    protected $description = 'Geef ticketreserveringen van verlopen (onbetaalde) bestellingen vrij';

    public function handle(): int
    {
        $expired = TicketOrder::query()
            ->where('status', OrderStatus::Pending)
            ->where('expires_at', '<', now())
            ->get();

        foreach ($expired as $order) {
            $order->releaseReservation();
        }

        $this->info("{$expired->count()} verlopen reservering(en) vrijgegeven.");

        return self::SUCCESS;
    }
}
