<?php

namespace App\Jobs;

use App\Enums\OrderStatus;
use App\Models\Setting;
use App\Models\TicketOrder;
use App\Services\KitApi;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Zet de koper van een betaalde ticketbestelling op de Kit-e-maillijst
 * (Instellingen → E-mailmarketing). Zonder API-key doet de job stil niets,
 * zodat de feature optioneel blijft. Kit upsert op e-mailadres, dus dubbele
 * uitvoering (webhook + bedankpagina) is onschadelijk.
 */
class SubscribeTicketBuyerToKitJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    /** @var array<int, int> */
    public array $backoff = [60, 300];

    public function __construct(public int $orderId)
    {
    }

    public function handle(): void
    {
        if (! $apiKey = KitApi::apiKey()) {
            return;
        }

        $order = TicketOrder::find($this->orderId);
        if (! $order || $order->status !== OrderStatus::Paid) {
            return;
        }

        $client = KitApi::client($apiKey);

        $client->post('/subscribers', array_filter([
            'email_address' => $order->buyer_email,
            'first_name' => str($order->buyer_name)->before(' ')->trim()->toString() ?: null,
        ]))->throw();

        if (filled($formId = Setting::get('kit_form_id'))) {
            $client->post("/forms/{$formId}/subscribers", [
                'email_address' => $order->buyer_email,
            ])->throw();
        }

        if (filled($tagId = Setting::get('kit_tag_id'))) {
            $client->post("/tags/{$tagId}/subscribers", [
                'email_address' => $order->buyer_email,
            ])->throw();
        }
    }
}
