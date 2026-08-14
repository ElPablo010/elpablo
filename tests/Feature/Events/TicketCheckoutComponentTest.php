<?php

/**
 * De Livewire-checkoutcomponent: rendering, aantallen en totalen (server-side),
 * kortingscode-preview, honeypot, validatie en de redirect naar Stripe. Plus de
 * locale-valkuil: /livewire/update heeft geen taalprefix, dus de component moet
 * zijn locale zelf vasthouden (PersistsLocale).
 */

use App\Contracts\PaymentGateway;
use App\Livewire\Events\TicketCheckout;
use App\Models\DiscountCode;
use App\Models\Event;
use App\Models\EventTicketDiscount;
use App\Models\EventTicketType;
use App\Models\TicketOrder;
use App\Models\TicketType;
use Livewire\Livewire;
use Tests\Fakes\FakePaymentGateway;

beforeEach(function () {
    $this->gateway = new FakePaymentGateway;
    app()->instance(PaymentGateway::class, $this->gateway);
});

function componentEvent(array $pivot = []): array
{
    $event = Event::factory()->create();
    $type = TicketType::factory()->create(['name' => 'Standaard', 'name_es' => 'Estándar']);
    EventTicketType::factory()->create($pivot + [
        'event_id' => $event->id,
        'ticket_type_id' => $type->id,
        'price' => 15,
    ]);

    return [$event->fresh(), $type];
}

it('renders ticket types and computes totals server-side', function () {
    [$event, $type] = componentEvent();

    Livewire::test(TicketCheckout::class, ['event' => $event])
        ->assertSee('Standaard')
        ->assertSee('€ 15,00')
        ->call('increment', $type->id)
        ->call('increment', $type->id)
        ->assertSee('€ 30,00');
});

it('shows promo prices and applies them to the total', function () {
    [$event, $type] = componentEvent();
    EventTicketDiscount::factory()->create([
        'event_id' => $event->id,
        'ticket_type_id' => $type->id,
        'name' => 'Early bird',
        'price' => 10,
    ]);

    Livewire::test(TicketCheckout::class, ['event' => $event->fresh()])
        ->assertSee('Early bird')
        ->call('increment', $type->id)
        ->assertSee('€ 10,00');
});

it('validates and applies a discount code with live feedback', function () {
    [$event, $type] = componentEvent();
    DiscountCode::factory()->fixed(5)->create(['code' => 'VIJF']);

    Livewire::test(TicketCheckout::class, ['event' => $event])
        ->call('increment', $type->id)
        ->set('discountCode', 'onbestaand')
        ->call('applyDiscountCode')
        ->assertSet('codeError', 'Deze kortingscode bestaat niet.')
        ->set('discountCode', 'vijf')
        ->call('applyDiscountCode')
        ->assertSet('appliedCode', 'VIJF')
        ->assertSet('codeError', null)
        ->assertSee('€ 10,00'); // 15 − 5
});

it('caps the quantity at the remaining capacity', function () {
    [$event, $type] = componentEvent(['capacity' => 2]);

    $component = Livewire::test(TicketCheckout::class, ['event' => $event]);
    foreach (range(1, 5) as $i) {
        $component->call('increment', $type->id);
    }

    $component->assertSet('quantities.'.$type->id, 2);
});

it('hides steppers for sold-out and closed ticket types', function () {
    [$event, $type] = componentEvent(['sold_out' => true]);

    Livewire::test(TicketCheckout::class, ['event' => $event])
        ->assertSee('Uitverkocht');

    [$event2] = componentEvent(['sales_end_date' => now()->subDay()->toDateString()]);

    Livewire::test(TicketCheckout::class, ['event' => $event2])
        ->assertSee('Verkoop afgesloten');
});

it('requires buyer details and at least one ticket', function () {
    [$event, $type] = componentEvent();

    Livewire::test(TicketCheckout::class, ['event' => $event])
        ->call('increment', $type->id)
        ->call('checkout')
        ->assertHasErrors(['buyerName', 'buyerEmail']);

    Livewire::test(TicketCheckout::class, ['event' => $event])
        ->set('buyerName', 'Test Koper')
        ->set('buyerEmail', 'koper@example.com')
        ->call('checkout')
        ->assertHasErrors(['quantities']);
});

it('silently ignores honeypot submissions', function () {
    [$event, $type] = componentEvent();

    Livewire::test(TicketCheckout::class, ['event' => $event])
        ->call('increment', $type->id)
        ->set('website', 'spambot')
        ->call('checkout')
        ->assertHasNoErrors();

    expect(TicketOrder::count())->toBe(0);
});

it('redirects to the Stripe checkout URL on a valid submit', function () {
    [$event, $type] = componentEvent();

    Livewire::test(TicketCheckout::class, ['event' => $event])
        ->call('increment', $type->id)
        ->set('buyerName', 'Test Koper')
        ->set('buyerEmail', 'koper@example.com')
        ->call('checkout')
        ->assertHasNoErrors()
        ->assertRedirect('https://checkout.stripe.test/cs_test_1');

    expect(TicketOrder::sole()->status->value)->toBe('pending');
});

it('surfaces capacity errors from the service as validation errors', function () {
    [$event, $type] = componentEvent(['capacity' => 1]);

    // Iemand anders reserveerde intussen het laatste ticket.
    app(\App\Services\TicketCheckoutService::class)->createSession(
        event: $event->fresh(),
        quantities: [$type->id => 1],
        buyerName: 'Snelle Koper',
        buyerEmail: 'snel@example.com',
        discountCode: null,
        locale: 'nl',
    );

    Livewire::test(TicketCheckout::class, ['event' => $event->fresh()])
        // De stepper klemt op restcapaciteit, maar die telt de andere
        // reservering al mee — forceer de aantallen dus rechtstreeks.
        ->set('quantities', [$type->id => 1])
        ->set('buyerName', 'Trage Koper')
        ->set('buyerEmail', 'traag@example.com')
        ->call('checkout')
        ->assertHasErrors(['quantities']);
});

it('keeps the Spanish locale across re-renders', function () {
    [$event, $type] = componentEvent();

    app()->setLocale('es');

    Livewire::test(TicketCheckout::class, ['event' => $event])
        ->assertSet('locale', 'es')
        ->assertSee('Estándar')
        ->call('increment', $type->id)
        ->assertSet('locale', 'es');
});
