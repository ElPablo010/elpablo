<?php

/**
 * De bestellingen-admin: resend-actie, refund-actie (Stripe + tickets ongeldig,
 * met nette afhandeling wanneer Stripe weigert).
 */

use App\Contracts\PaymentGateway;
use App\Enums\OrderStatus;
use App\Enums\TicketStatus;
use App\Filament\Resources\TicketOrders\Pages\ViewTicketOrder;
use App\Jobs\SendTicketOrderEmailJob;
use App\Models\Event;
use App\Models\EventTicket;
use App\Models\TicketOrder;
use App\Models\TicketType;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;
use Tests\Fakes\FakePaymentGateway;

beforeEach(function () {
    $this->gateway = new FakePaymentGateway;
    app()->instance(PaymentGateway::class, $this->gateway);
    $this->actingAs(admin());
});

function adminOrder(): TicketOrder
{
    $event = Event::factory()->create();
    $type = TicketType::factory()->create();
    $order = TicketOrder::factory()->paid()->create(['event_id' => $event->id]);
    EventTicket::factory()->count(2)->create([
        'event_id' => $event->id,
        'ticket_type_id' => $type->id,
        'ticket_order_id' => $order->id,
    ]);

    return $order->fresh();
}

it('dispatches a forced resend from the order view', function () {
    Queue::fake();
    $order = adminOrder();

    Livewire::test(ViewTicketOrder::class, ['record' => $order->getRouteKey()])
        ->callAction('resend')
        ->assertHasNoActionErrors();

    Queue::assertPushed(SendTicketOrderEmailJob::class, fn (SendTicketOrderEmailJob $job) => $job->orderId === $order->id && $job->force === true);
});

it('refunds an order via Stripe and invalidates the tickets', function () {
    $order = adminOrder();

    Livewire::test(ViewTicketOrder::class, ['record' => $order->getRouteKey()])
        ->callAction('refund')
        ->assertHasNoActionErrors();

    $order = $order->fresh();
    expect($this->gateway->refunds)->toBe([$order->stripe_payment_intent_id])
        ->and($order->status)->toBe(OrderStatus::Refunded)
        ->and($order->refunded_at)->not->toBeNull()
        ->and($order->tickets()->where('status', TicketStatus::Refunded)->count())->toBe(2);
});

it('keeps the order untouched when Stripe refuses the refund', function () {
    $order = adminOrder();

    $failing = Mockery::mock(PaymentGateway::class);
    $failing->shouldReceive('createRefund')->once()->andThrow(new RuntimeException('Refund geweigerd'));
    app()->instance(PaymentGateway::class, $failing);

    Livewire::test(ViewTicketOrder::class, ['record' => $order->getRouteKey()])
        ->callAction('refund');

    $order = $order->fresh();
    expect($order->status)->toBe(OrderStatus::Paid)
        ->and($order->tickets()->where('status', TicketStatus::Refunded)->count())->toBe(0);
});

it('hides the refund action for pending orders', function () {
    $order = adminOrder();
    $order->update(['status' => OrderStatus::Pending, 'paid_at' => null]);

    Livewire::test(ViewTicketOrder::class, ['record' => $order->getRouteKey()])
        ->assertActionHidden('refund')
        ->assertActionHidden('resend');
});
