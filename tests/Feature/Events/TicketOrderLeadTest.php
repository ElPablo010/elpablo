<?php

/**
 * Leads-meetlaag × ticketverkoop: een betaalde bestelling is een lead, mét de
 * herkomst van de koper. De webhook heeft geen bezoekerssessie, dus de
 * first touch reist mee in de pending-payload; de lead wordt bínnen de
 * idempotency-guard geschreven, zodat webhook + bedankpagina nooit dubbel tellen.
 */

use App\Contracts\PaymentGateway;
use App\Models\Event;
use App\Models\EventTicketType;
use App\Models\Lead;
use App\Models\PendingStripeSession;
use App\Models\TicketOrder;
use App\Models\TicketType;
use App\Services\TicketCheckoutService;
use App\Services\TicketOrderFulfillment;
use App\Support\Attribution;
use Illuminate\Support\Facades\Queue;
use Tests\Fakes\FakePaymentGateway;

beforeEach(function () {
    Queue::fake();
    app()->instance(PaymentGateway::class, new FakePaymentGateway);
});

function leadOrder(): TicketOrder
{
    $event = Event::factory()->create(['slug' => 'latin-night', 'name' => 'Latin Night']);
    $type = TicketType::factory()->create();
    EventTicketType::factory()->create(['event_id' => $event->id, 'ticket_type_id' => $type->id, 'price' => 15]);

    app(TicketCheckoutService::class)->createSession(
        event: $event->fresh(),
        quantities: [$type->id => 2],
        buyerName: 'Test Koper',
        buyerEmail: 'koper@example.com',
        discountCode: null,
        locale: 'es',
    );

    return TicketOrder::sole();
}

function completeOrder(TicketOrder $order): ?TicketOrder
{
    $pending = PendingStripeSession::sole();

    return app(TicketOrderFulfillment::class)->complete(
        (object) ['id' => $order->stripe_session_id, 'payment_intent' => 'pi_fake_1'],
        $pending->uuid,
        $pending->payload,
    );
}

it('zet geen attribution-sleutel in de pending-payload zonder snapshot', function () {
    leadOrder();

    expect(PendingStripeSession::sole()->payload)->not->toHaveKey('attribution');
});

it('neemt de herkomst mee in de pending-payload als er een snapshot is', function () {
    session([Attribution::SESSION_KEY => ['channel' => Attribution::CHANNEL_SOCIAL, 'referrer_host' => 'instagram.com', 'landing_path' => '/events']]);
    leadOrder();

    expect(PendingStripeSession::sole()->payload['attribution']['channel'])->toBe(Attribution::CHANNEL_SOCIAL);
});

it('registreert een betaalde bestelling als lead met bedrag, herkomst en taal', function () {
    session([Attribution::SESSION_KEY => ['channel' => Attribution::CHANNEL_SOCIAL, 'referrer_host' => 'instagram.com', 'landing_path' => '/events/latin-night']]);
    $order = leadOrder();

    expect(Lead::count())->toBe(0);

    // De webhook draait in een ander verzoek: de sessie is daar niet.
    session()->forget(Attribution::SESSION_KEY);
    $payload = PendingStripeSession::sole()->payload;
    $uuid = PendingStripeSession::sole()->uuid;
    $session = (object) ['id' => $order->stripe_session_id, 'payment_intent' => 'pi_fake_1'];

    app(TicketOrderFulfillment::class)->complete($session, $uuid, $payload);

    $lead = Lead::sole();
    expect($lead->lead_type)->toBe(Lead::TYPE_TICKET_ORDER)
        ->and($lead->source_type)->toBe($order->getMorphClass())
        ->and($lead->source_id)->toBe($order->id)
        ->and((float) $lead->value)->toBe(30.0)
        ->and($lead->channel)->toBe(Attribution::CHANNEL_SOCIAL)
        ->and($lead->landing_path)->toBe('/events/latin-night')
        ->and($lead->locale)->toBe('es')
        ->and($lead->typeLabel())->toBe('Ticketaankoop');

    // Dubbele delivery (bedankpagina ná de webhook): geen tweede lead.
    app(TicketOrderFulfillment::class)->complete($session, $uuid, $payload);
    expect(Lead::count())->toBe(1);
});

it('registreert de lead ook zonder herkomst', function () {
    $order = leadOrder();
    completeOrder($order);

    expect(Lead::sole())->channel->toBeNull()->lead_type->toBe(Lead::TYPE_TICKET_ORDER);
});
