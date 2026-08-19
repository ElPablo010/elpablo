<?php

/**
 * Kit-koppeling: elke betaalde bestelling zet de koper op de Kit-lijst.
 * Zonder API-key doet de job stil niets.
 */

use App\Jobs\SubscribeTicketBuyerToKitJob;
use App\Models\Event;
use App\Models\Setting;
use App\Models\TicketOrder;
use Illuminate\Support\Facades\Http;

function kitOrder(): TicketOrder
{
    return TicketOrder::factory()->paid()->create([
        'event_id' => Event::factory()->create()->id,
        'buyer_name' => 'Pieter Van Looy',
        'buyer_email' => 'koper@example.com',
    ]);
}

it('does nothing without an api key', function () {
    Http::fake();

    (new SubscribeTicketBuyerToKitJob(kitOrder()->id))->handle();

    Http::assertNothingSent();
});

it('subscribes the buyer and applies form and tag when configured', function () {
    Setting::set('kit_api_key', 'kit-secret');
    Setting::set('kit_form_id', '123');
    Setting::set('kit_tag_id', '456');

    Http::fake(['api.kit.com/*' => Http::response(['subscriber' => ['id' => 1]], 200)]);

    (new SubscribeTicketBuyerToKitJob(kitOrder()->id))->handle();

    Http::assertSent(fn ($request) => $request->url() === 'https://api.kit.com/v4/subscribers'
        && $request['email_address'] === 'koper@example.com'
        && $request['first_name'] === 'Pieter'
        && $request->hasHeader('X-Kit-Api-Key', 'kit-secret'));
    Http::assertSent(fn ($request) => $request->url() === 'https://api.kit.com/v4/forms/123/subscribers');
    Http::assertSent(fn ($request) => $request->url() === 'https://api.kit.com/v4/tags/456/subscribers');
});

it('skips unpaid orders', function () {
    Setting::set('kit_api_key', 'kit-secret');
    Http::fake();

    $order = TicketOrder::factory()->create(['event_id' => Event::factory()->create()->id]);

    (new SubscribeTicketBuyerToKitJob($order->id))->handle();

    Http::assertNothingSent();
});
