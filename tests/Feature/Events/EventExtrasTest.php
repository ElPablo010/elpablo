<?php

/**
 * Extra's bij een bestelling (een gratis groepstafel, later een drankkaart).
 * De kern van deze laag: een extra is GEEN ticket — ze telt nooit mee als
 * bezoeker — en ze komt pas vrij vanaf een instelbaar aantal tickets, met een
 * eigen voorraad en een maximum per bestelling.
 */

use App\Contracts\PaymentGateway;
use App\Enums\OrderStatus;
use App\Exceptions\CheckoutException;
use App\Livewire\Events\TicketCheckout;
use App\Models\Event;
use App\Models\EventExtra;
use App\Models\EventTicket;
use App\Models\EventTicketType;
use App\Models\TicketOrder;
use App\Models\TicketType;
use App\Services\TicketCheckoutService;
use Livewire\Livewire;
use Tests\Fakes\FakePaymentGateway;

beforeEach(function () {
    app()->instance(PaymentGateway::class, new FakePaymentGateway);
});

/**
 * Een event met één tickettype van € 15 en één extra.
 *
 * @return array{0: Event, 1: TicketType, 2: EventExtra}
 */
function extraEvent(array $extra = [], array $pivot = []): array
{
    $event = Event::factory()->create();
    $type = TicketType::factory()->create(['name' => 'Standaard', 'name_en' => 'Standard']);
    EventTicketType::factory()->create($pivot + [
        'event_id' => $event->id,
        'ticket_type_id' => $type->id,
        'price' => 15,
    ]);

    $model = EventExtra::factory()->create($extra + ['event_id' => $event->id]);

    return [$event->fresh(), $type, $model->fresh()];
}

function checkoutWithExtra(Event $event, TicketType $type, int $tickets, array $extras, string $email = 'koper@example.com'): string
{
    return app(TicketCheckoutService::class)->createSession(
        event: $event,
        quantities: [$type->id => $tickets],
        buyerName: 'Test Koper',
        buyerEmail: $email,
        discountCode: null,
        locale: 'nl',
        extras: $extras,
    );
}

/* -----------------------------------------------------------------
 |  Geen bezoeker
 | ----------------------------------------------------------------- */

it('creates no ticket for an extra, so visitor counts stay exact', function () {
    [$event, $type, $extra] = extraEvent();

    checkoutWithExtra($event, $type, 12, [$extra->id => 1]);

    $order = TicketOrder::firstOrFail();

    expect($order->tickets)->toHaveCount(12)
        ->and($order->extras)->toHaveCount(1)
        ->and(EventTicket::where('event_id', $event->id)->count())->toBe(12)
        ->and($order->extras->first()->description)->toBe('Groepstafel');
});

/* -----------------------------------------------------------------
 |  Drempel
 | ----------------------------------------------------------------- */

it('refuses an extra below the ticket threshold', function () {
    [$event, $type, $extra] = extraEvent();

    expect(fn () => checkoutWithExtra($event, $type, 11, [$extra->id => 1]))
        ->toThrow(CheckoutException::class, '"Groepstafel" kan pas vanaf 12 tickets.');
});

it('counts only the linked ticket type when one is set', function () {
    [$event, $type, $extra] = extraEvent();

    $vip = TicketType::factory()->create(['name' => 'VIP']);
    EventTicketType::factory()->create([
        'event_id' => $event->id,
        'ticket_type_id' => $vip->id,
        'price' => 25,
    ]);
    $extra->update(['ticket_type_id' => $type->id]);

    // 6 standaard + 6 VIP = 12 tickets, maar de drempel telt enkel standaard.
    expect(fn () => app(TicketCheckoutService::class)->createSession(
        event: $event->fresh(),
        quantities: [$type->id => 6, $vip->id => 6],
        buyerName: 'Test Koper',
        buyerEmail: 'koper@example.com',
        discountCode: null,
        locale: 'nl',
        extras: [$extra->id => 1],
    ))->toThrow(CheckoutException::class, 'vanaf 12 tickets');
});

it('allows an extra without a threshold', function () {
    [$event, $type, $extra] = extraEvent(['min_tickets' => 0]);

    checkoutWithExtra($event, $type, 1, [$extra->id => 1]);

    expect(TicketOrder::firstOrFail()->extras)->toHaveCount(1);
});

/* -----------------------------------------------------------------
 |  Maximum en voorraad
 | ----------------------------------------------------------------- */

it('refuses more than the maximum per order', function () {
    [$event, $type, $extra] = extraEvent();

    expect(fn () => checkoutWithExtra($event, $type, 12, [$extra->id => 2]))
        ->toThrow(CheckoutException::class, 'Je kunt maximaal 1 × "Groepstafel" per bestelling kiezen.');
});

it('runs out of stock and refuses the next claim', function () {
    [$event, $type, $extra] = extraEvent(['capacity' => 2]);

    checkoutWithExtra($event, $type, 12, [$extra->id => 1], 'een@example.com');
    checkoutWithExtra($event->fresh(), $type, 12, [$extra->id => 1], 'twee@example.com');

    expect($extra->fresh()->remainingCapacity())->toBe(0)
        ->and($extra->fresh()->isSoldOut())->toBeTrue()
        ->and(fn () => checkoutWithExtra($event->fresh(), $type, 12, [$extra->id => 1], 'drie@example.com'))
        ->toThrow(CheckoutException::class, '"Groepstafel" is volzet.');
});

it('frees stock when a reservation expires or is refunded', function () {
    [$event, $type, $extra] = extraEvent(['capacity' => 1]);

    checkoutWithExtra($event, $type, 12, [$extra->id => 1]);
    $order = TicketOrder::firstOrFail();

    expect($extra->fresh()->remainingCapacity())->toBe(0);

    // Verlopen reservering: de bestelling wordt Verlopen, de tafel komt vrij —
    // zonder dat er op de claim zelf iets bijgewerkt hoeft te worden.
    $order->update(['expires_at' => now()->subMinute()]);
    $order->releaseReservation();

    expect($order->fresh()->status)->toBe(OrderStatus::Expired)
        ->and($extra->fresh()->remainingCapacity())->toBe(1)
        ->and($order->fresh()->extras)->toHaveCount(1); // het spoor blijft bestaan

    // Betaald en daarna terugbetaald: idem.
    $order->update(['status' => OrderStatus::Paid, 'expires_at' => null]);
    expect($extra->fresh()->remainingCapacity())->toBe(0);

    $order->update(['status' => OrderStatus::Refunded, 'refunded_at' => now()]);
    expect($extra->fresh()->remainingCapacity())->toBe(1);
});

it('refuses an extra that is manually marked as fully booked', function () {
    [$event, $type, $extra] = extraEvent(['sold_out' => true]);

    expect(fn () => checkoutWithExtra($event, $type, 12, [$extra->id => 1]))
        ->toThrow(CheckoutException::class, '"Groepstafel" is niet meer beschikbaar.');
});

/* -----------------------------------------------------------------
 |  Prijzen
 | ----------------------------------------------------------------- */

it('adds a paid extra to the order total but keeps it out of the discount base', function () {
    [$event, $type, $extra] = extraEvent(['price' => 20, 'min_tickets' => 0]);

    checkoutWithExtra($event, $type, 2, [$extra->id => 1]);

    $order = TicketOrder::firstOrFail();

    // 2 × € 15 + € 20 = € 50.
    expect((float) $order->subtotal_inc_vat)->toBe(50.0)
        ->and((float) $order->total_inc_vat)->toBe(50.0)
        ->and($order->extrasTotal())->toBe(20.0)
        ->and((float) $order->extras->first()->line_total_inc_vat)->toBe(20.0);
});

/* -----------------------------------------------------------------
 |  De checkout-component
 | ----------------------------------------------------------------- */

it('shows the extra locked until the threshold is reached', function () {
    [$event, $type, $extra] = extraEvent();

    $component = Livewire::test(TicketCheckout::class, ['event' => $event])
        ->assertSee('Groepstafel')
        ->assertSee('Vanaf 12 tickets')
        ->assertDontSee('Toevoegen');

    $component->set('quantities.'.$type->id, 12)
        ->assertDontSee('Vanaf 12 tickets')
        ->assertSee('Toevoegen')
        ->call('toggleExtra', $extra->id)
        ->assertSet('selectedExtras.'.$extra->id, 1)
        ->assertSee('Toegevoegd');
});

it('drops a selected extra when the ticket count falls below the threshold', function () {
    [$event, $type, $extra] = extraEvent();

    Livewire::test(TicketCheckout::class, ['event' => $event])
        ->set('quantities.'.$type->id, 12)
        ->call('toggleExtra', $extra->id)
        ->assertSet('selectedExtras.'.$extra->id, 1)
        ->call('decrement', $type->id)
        ->assertSet('selectedExtras.'.$extra->id, 0);
});

it('caps a typed ticket quantity at the maximum', function () {
    [$event, $type] = extraEvent([], ['capacity' => 20]);

    Livewire::test(TicketCheckout::class, ['event' => $event])
        ->set('quantities.'.$type->id, 999)
        ->assertSet('quantities.'.$type->id, 20);
});

it('carries the extra through to the order and the total', function () {
    [$event, $type, $extra] = extraEvent(['price' => 10]);

    Livewire::test(TicketCheckout::class, ['event' => $event])
        ->set('quantities.'.$type->id, 12)
        ->call('toggleExtra', $extra->id)
        ->set('buyerName', 'Groep Test')
        ->set('buyerEmail', 'groep@example.com')
        ->call('checkout');

    $order = TicketOrder::firstOrFail();

    expect($order->extras)->toHaveCount(1)
        ->and((float) $order->total_inc_vat)->toBe(190.0) // 12 × 15 + 10
        ->and($order->tickets)->toHaveCount(12);
});

it('refuses a tampered extra selection server-side', function () {
    [$event, $type, $extra] = extraEvent();

    // De bezoeker knoeit met de Livewire-state: extra aan, tickets onder de drempel.
    Livewire::test(TicketCheckout::class, ['event' => $event])
        ->set('quantities.'.$type->id, 12)
        ->call('toggleExtra', $extra->id)
        ->set('quantities.'.$type->id, 2)
        ->set('selectedExtras.'.$extra->id, 1)
        ->set('buyerName', 'Sluwe Koper')
        ->set('buyerEmail', 'sluw@example.com')
        ->call('checkout')
        ->assertHasErrors('quantities');

    expect(TicketOrder::count())->toBe(0);
});
