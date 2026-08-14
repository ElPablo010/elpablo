<?php

/**
 * TicketCheckoutService: van ticketselectie naar Stripe-sessie, met
 * reservering (order + items + reserved tickets + pending-rij) in één
 * transactie, en volledige terugrol wanneer Stripe faalt.
 */

use App\Contracts\PaymentGateway;
use App\Enums\OrderStatus;
use App\Enums\TicketStatus;
use App\Exceptions\CheckoutException;
use App\Models\DiscountCode;
use App\Models\Event;
use App\Models\EventTicket;
use App\Models\EventTicketType;
use App\Models\PendingStripeSession;
use App\Models\TicketOrder;
use App\Models\TicketType;
use App\Services\TicketCheckoutService;
use Tests\Fakes\FakePaymentGateway;

beforeEach(function () {
    $this->gateway = new FakePaymentGateway;
    app()->instance(PaymentGateway::class, $this->gateway);
});

function checkoutEvent(array $pivot = [], array $attributes = []): array
{
    $event = Event::factory()->create($attributes);
    $type = TicketType::factory()->create(['name' => 'Standaard']);
    EventTicketType::factory()->create($pivot + [
        'event_id' => $event->id,
        'ticket_type_id' => $type->id,
        'price' => 15,
    ]);

    return [$event->fresh(), $type];
}

function createSession(Event $event, array $quantities, ?string $code = null, string $email = 'koper@example.com', string $locale = 'nl'): string
{
    return app(TicketCheckoutService::class)->createSession(
        event: $event,
        quantities: $quantities,
        buyerName: 'Test Koper',
        buyerEmail: $email,
        discountCode: $code,
        locale: $locale,
    );
}

it('creates the order, items, reserved tickets, pending row and Stripe session', function () {
    [$event, $type] = checkoutEvent(attributes: ['slug' => 'latin-night', 'name' => 'Latin Night']);

    $url = createSession($event, [$type->id => 2]);

    expect($url)->toBe('https://checkout.stripe.test/cs_test_1');

    $order = TicketOrder::sole();
    expect($order->status)->toBe(OrderStatus::Pending)
        ->and((float) $order->total_inc_vat)->toBe(30.0)
        ->and($order->stripe_session_id)->toBe('cs_test_1')
        ->and($order->expires_at)->not->toBeNull()
        ->and($order->items()->count())->toBe(1)
        ->and($order->tickets()->where('status', TicketStatus::Reserved)->count())->toBe(2)
        ->and(PendingStripeSession::count())->toBe(1);

    $params = $this->gateway->createdSessions[0];
    expect($params['line_items'])->toHaveCount(1)
        ->and($params['line_items'][0]['price_data']['unit_amount'])->toBe(3000)
        ->and($params['line_items'][0]['quantity'])->toBe(1)
        ->and($params['client_reference_id'])->toBe(PendingStripeSession::sole()->uuid)
        ->and($params['customer_email'])->toBe('koper@example.com')
        ->and($params['expires_at'])->toBeGreaterThan(now()->addMinutes(29)->getTimestamp())
        ->and($params['success_url'])->toContain('/events/latin-night/bedankt?session_id=');
});

it('localizes the success and cancel URLs', function () {
    [$event, $type] = checkoutEvent(attributes: ['slug' => 'latin-night']);

    createSession($event, [$type->id => 1], locale: 'es');

    $params = $this->gateway->createdSessions[0];
    expect($params['success_url'])->toContain('/es/events/latin-night/bedankt')
        ->and($params['cancel_url'])->toContain('/es/events/latin-night');
});

it('applies a discount code to the Stripe amount', function () {
    [$event, $type] = checkoutEvent();
    DiscountCode::factory()->fixed(10)->create(['code' => 'TIENEURO']);

    createSession($event, [$type->id => 2], code: 'TIENEURO');

    $order = TicketOrder::sole();
    expect((float) $order->subtotal_inc_vat)->toBe(30.0)
        ->and((float) $order->total_inc_vat)->toBe(20.0)
        ->and((float) $order->discount_amount)->toBe(10.0)
        ->and($this->gateway->createdSessions[0]['line_items'][0]['price_data']['unit_amount'])->toBe(2000);
});

it('rejects totals below the Stripe minimum of fifty cents', function () {
    [$event, $type] = checkoutEvent();
    DiscountCode::factory()->create(['code' => 'ALLES', 'type' => 'percentage', 'value' => 100]);

    expect(fn () => createSession($event, [$type->id => 2], code: 'ALLES'))
        ->toThrow(CheckoutException::class, 'Het totaalbedrag moet minstens € 0,50 zijn.');

    expect(TicketOrder::count())->toBe(0)
        ->and(EventTicket::count())->toBe(0);
});

it('rejects closed sales windows, sold-out flags, cancelled and draft events', function () {
    [$event, $type] = checkoutEvent(['sales_end_date' => now()->subDay()->toDateString()]);
    expect(fn () => createSession($event, [$type->id => 1]))
        ->toThrow(CheckoutException::class);

    [$event2, $type2] = checkoutEvent(['sold_out' => true]);
    expect(fn () => createSession($event2->fresh(), [$type2->id => 1]))
        ->toThrow(CheckoutException::class, '"Standaard" is uitverkocht.');

    $cancelled = Event::factory()->cancelled()->create();
    expect(fn () => createSession($cancelled, []))
        ->toThrow(CheckoutException::class, 'Dit event is niet (meer) beschikbaar.');

    $draft = Event::factory()->draft()->create();
    expect(fn () => createSession($draft, []))
        ->toThrow(CheckoutException::class, 'Dit event is niet (meer) beschikbaar.');
});

it('rolls back the reservation when Stripe fails', function () {
    [$event, $type] = checkoutEvent();
    $this->gateway->failOnCreate = true;

    expect(fn () => createSession($event, [$type->id => 2]))
        ->toThrow(CheckoutException::class, 'De betaalpagina kon niet gestart worden. Probeer het zo meteen opnieuw.');

    expect(TicketOrder::count())->toBe(0)
        ->and(EventTicket::count())->toBe(0)
        ->and(PendingStripeSession::count())->toBe(0);
});
