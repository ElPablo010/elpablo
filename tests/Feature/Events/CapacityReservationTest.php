<?php

/**
 * Capaciteitsbewaking: reserveringen tellen mee, overschrijding wordt geweigerd
 * met een nette melding, en verlopen reserveringen geven hun plek weer vrij.
 */

use App\Contracts\PaymentGateway;
use App\Enums\OrderStatus;
use App\Enums\TicketStatus;
use App\Exceptions\CheckoutException;
use App\Models\Event;
use App\Models\EventTicket;
use App\Models\EventTicketType;
use App\Models\PendingStripeSession;
use App\Models\TicketOrder;
use App\Models\TicketType;
use App\Services\TicketCheckoutService;
use Tests\Fakes\FakePaymentGateway;

beforeEach(function () {
    app()->instance(PaymentGateway::class, new FakePaymentGateway);
});

function capacityEvent(?int $capacity): array
{
    $event = Event::factory()->create();
    $type = TicketType::factory()->create(['name' => 'Standaard']);
    EventTicketType::factory()->create([
        'event_id' => $event->id,
        'ticket_type_id' => $type->id,
        'price' => 15,
        'capacity' => $capacity,
    ]);

    return [$event->fresh(), $type];
}

function reserve(Event $event, TicketType $type, int $qty, string $email = 'koper@example.com'): string
{
    return app(TicketCheckoutService::class)->createSession(
        event: $event,
        quantities: [$type->id => $qty],
        buyerName: 'Test Koper',
        buyerEmail: $email,
        discountCode: null,
        locale: 'nl',
    );
}

it('reserves within capacity and refuses beyond it', function () {
    [$event, $type] = capacityEvent(3);

    reserve($event, $type, 2);

    // Nog 1 plek: 2 vragen faalt met de restboodschap …
    expect(fn () => reserve($event->fresh(), $type, 2, 'tweede@example.com'))
        ->toThrow(CheckoutException::class, 'Er zijn nog maar 1 tickets beschikbaar voor "Standaard".');

    // … 1 vragen lukt nog, daarna is het echt vol.
    reserve($event->fresh(), $type, 1, 'derde@example.com');

    expect(fn () => reserve($event->fresh(), $type, 1, 'vierde@example.com'))
        ->toThrow(CheckoutException::class, '"Standaard" is uitverkocht.');
});

it('treats null capacity as unlimited', function () {
    [$event, $type] = capacityEvent(null);

    reserve($event, $type, 10);
    reserve($event->fresh(), $type, 10, 'tweede@example.com');

    expect(EventTicket::count())->toBe(20);
});

it('counts paid and checked-in tickets against capacity but not refunded ones', function () {
    [$event, $type] = capacityEvent(2);
    $order = TicketOrder::factory()->paid()->create(['event_id' => $event->id]);
    EventTicket::factory()->create(['event_id' => $event->id, 'ticket_type_id' => $type->id, 'ticket_order_id' => $order->id, 'status' => TicketStatus::CheckedIn]);
    EventTicket::factory()->create(['event_id' => $event->id, 'ticket_type_id' => $type->id, 'ticket_order_id' => $order->id, 'status' => TicketStatus::Refunded]);

    // 1 van 2 plekken bezet (refunded telt niet): 1 reserveren kan, 2 niet.
    expect(fn () => reserve($event->fresh(), $type, 2))
        ->toThrow(CheckoutException::class);

    reserve($event->fresh(), $type, 1);
});

it('releases expired reservations via the command and frees capacity', function () {
    [$event, $type] = capacityEvent(2);

    reserve($event, $type, 2);
    $order = TicketOrder::sole();

    // Nog niet verlopen: command doet niets.
    $this->artisan('events:release-expired-reservations');
    expect($order->fresh()->status)->toBe(OrderStatus::Pending);

    // Verlopen: tickets weg, order Expired, pending-rij weg, capaciteit vrij.
    $order->update(['expires_at' => now()->subMinute()]);
    $this->artisan('events:release-expired-reservations');

    $order = $order->fresh();
    expect($order->status)->toBe(OrderStatus::Expired)
        ->and($order->tickets()->count())->toBe(0)
        ->and(PendingStripeSession::count())->toBe(0);

    reserve($event->fresh(), $type, 2, 'nieuwe@example.com');
});
