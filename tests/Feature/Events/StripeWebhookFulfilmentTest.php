<?php

/**
 * Webhook + fulfilment: signature-paden, geslaagde afronding, idempotente
 * dubbele delivery, de bedankpagina-fallback, alarmmail bij fouten (met 500
 * zodat Stripe retryt) en de vrijgave bij checkout.session.expired.
 */

use App\Contracts\PaymentGateway;
use App\Enums\OrderStatus;
use App\Enums\TicketStatus;
use App\Jobs\SendTicketOrderEmailJob;
use App\Mail\TicketOrderFailedMail;
use App\Models\Event;
use App\Models\EventTicketType;
use App\Models\PendingStripeSession;
use App\Models\Setting;
use App\Models\TicketOrder;
use App\Models\TicketType;
use App\Services\TicketCheckoutService;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Tests\Fakes\FakePaymentGateway;

beforeEach(function () {
    $this->gateway = new FakePaymentGateway;
    app()->instance(PaymentGateway::class, $this->gateway);
    Setting::set('stripe_webhook_secret', 'whsec_test');
});

/**
 * Volledige aanloop: reservering + Stripe-sessie via de echte service, zodat
 * de webhook exact dezelfde data ziet als in productie.
 */
function pendingOrder(): TicketOrder
{
    $event = Event::factory()->create(['slug' => 'latin-night', 'name' => 'Latin Night']);
    $type = TicketType::factory()->create();
    EventTicketType::factory()->create([
        'event_id' => $event->id,
        'ticket_type_id' => $type->id,
        'price' => 15,
    ]);

    app(TicketCheckoutService::class)->createSession(
        event: $event->fresh(),
        quantities: [$type->id => 2],
        buyerName: 'Test Koper',
        buyerEmail: 'koper@example.com',
        discountCode: null,
        locale: 'nl',
    );

    return TicketOrder::sole();
}

function webhookPayload(TicketOrder $order, string $type = 'checkout.session.completed'): array
{
    return [
        'type' => $type,
        'data' => ['object' => [
            'id' => $order->stripe_session_id,
            'payment_intent' => 'pi_fake_1',
            'client_reference_id' => PendingStripeSession::sole()->uuid,
        ]],
    ];
}

function postWebhook(array $payload, string $signature = 'valid-signature')
{
    return test()->call('POST', '/stripe/webhook', server: [
        'HTTP_STRIPE_SIGNATURE' => $signature,
        'CONTENT_TYPE' => 'application/json',
    ], content: json_encode($payload));
}

it('rejects an invalid signature with 400 and a missing secret with 500', function () {
    $order = pendingOrder();

    postWebhook(webhookPayload($order), signature: 'wrong')->assertStatus(400);

    Setting::set('stripe_webhook_secret', null);
    config(['services.stripe.webhook_secret' => null]);
    postWebhook(webhookPayload($order))->assertStatus(500);

    expect($order->fresh()->status)->toBe(OrderStatus::Pending);
});

it('completes the order on checkout.session.completed', function () {
    Queue::fake();
    $order = pendingOrder();

    postWebhook(webhookPayload($order))->assertOk();

    $order = $order->fresh();
    expect($order->status)->toBe(OrderStatus::Paid)
        ->and($order->paid_at)->not->toBeNull()
        ->and($order->stripe_payment_intent_id)->toBe('pi_fake_1')
        ->and($order->tickets()->where('status', TicketStatus::Paid)->count())->toBe(2)
        ->and(PendingStripeSession::count())->toBe(0);

    Queue::assertPushed(SendTicketOrderEmailJob::class, 1);
});

it('is idempotent on duplicate webhook delivery', function () {
    Queue::fake();
    $order = pendingOrder();
    $payload = webhookPayload($order);

    postWebhook($payload)->assertOk();
    postWebhook($payload)->assertOk(); // pending-rij is weg → stille no-op

    expect($order->fresh()->tickets()->count())->toBe(2);
});

it('fulfils via the thanks page and makes the later webhook a no-op', function () {
    Queue::fake();
    $order = pendingOrder();
    $payload = webhookPayload($order);

    $this->get('/events/latin-night/bedankt?session_id='.$order->stripe_session_id)
        ->assertOk()
        ->assertSee('Bedankt voor je bestelling')
        ->assertSee('koper@example.com');

    expect($order->fresh()->status)->toBe(OrderStatus::Paid);

    // De webhook komt later binnen: geen dubbele verwerking.
    postWebhook($payload)->assertOk();
    expect($order->fresh()->tickets()->count())->toBe(2);
});

it('shows the processing message when the thanks page cannot confirm the payment', function () {
    Event::factory()->create(['slug' => 'latin-night']);

    $this->get('/events/latin-night/bedankt?session_id=cs_onbekend')
        ->assertOk()
        ->assertSee('We verwerken je betaling');
});

it('returns 500 and mails the admin once when fulfilment throws', function () {
    Mail::fake();
    Setting::set('admin_notification_email', 'pieter@dewebgoeroe.be');
    $order = pendingOrder();
    $payload = webhookPayload($order);

    // Forceer een fout diep in de fulfilment: de bestelling is verdwenen maar
    // de pending-rij bestaat nog én de update gooit door een DB-trigger… De
    // eenvoudigste betrouwbare kunstgreep: verwijder de order-rij hard, en laat
    // complete() op de ontbrekende relatie lopen — dat pad logt en levert null.
    // Voor het échte throw-pad mocken we de fulfilment-service.
    $mock = Mockery::mock(App\Services\TicketOrderFulfillment::class);
    $mock->shouldReceive('complete')->twice()->andThrow(new RuntimeException('DB down'));
    app()->instance(App\Services\TicketOrderFulfillment::class, $mock);

    postWebhook($payload)->assertStatus(500);
    postWebhook($payload)->assertStatus(500);

    // Slechts één alarmmail ondanks twee retries (cache-dedup per uuid).
    Mail::assertSent(TicketOrderFailedMail::class, 1);
    Mail::assertSent(TicketOrderFailedMail::class, fn (TicketOrderFailedMail $mail) => $mail->hasTo('pieter@dewebgoeroe.be'));
});

it('releases the reservation on checkout.session.expired', function () {
    $order = pendingOrder();

    postWebhook(webhookPayload($order, type: 'checkout.session.expired'))->assertOk();

    $order = $order->fresh();
    expect($order->status)->toBe(OrderStatus::Expired)
        ->and($order->tickets()->count())->toBe(0)
        ->and(PendingStripeSession::count())->toBe(0);
});
