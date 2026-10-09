<?php

/**
 * Resultaat per event (tab Resultaat + Tickets → Resultaten): alles excl. btw.
 * Online tickets komen uit de betaalde bestellingen — terugbetaald telt niet,
 * een kortingscode drukt enkel de tickets, nooit de extra's. Kosten en andere
 * opbrengsten zijn omschrijving + bedrag excl. btw.
 */

use App\Enums\OrderStatus;
use App\Enums\TicketStatus;
use App\Filament\Pages\EventResults;
use App\Filament\Resources\Events\Pages\EditEvent;
use App\Models\Event;
use App\Models\EventTicket;
use App\Models\TicketOrder;
use App\Models\TicketType;
use App\Support\EventResult;
use Livewire\Livewire;

function paidOrderFor(Event $event, array $items, array $extras = [], float $discount = 0, OrderStatus $status = OrderStatus::Paid): TicketOrder
{
    $order = TicketOrder::factory()->paid()->create([
        'event_id' => $event->id,
        'status' => $status,
        'discount_amount' => $discount ?: null,
    ]);

    foreach ($items as [$total, $rate, $quantity]) {
        $type = TicketType::factory()->create();
        $order->items()->create([
            'ticket_type_id' => $type->id,
            'description' => $type->name,
            'quantity' => $quantity,
            'unit_price_inc_vat' => $total / $quantity,
            'vat_rate' => $rate,
            'line_total_inc_vat' => $total,
        ]);
        EventTicket::factory()->count($quantity)->create([
            'event_id' => $event->id,
            'ticket_order_id' => $order->id,
            'ticket_type_id' => $type->id,
            'status' => $status === OrderStatus::Paid ? TicketStatus::Paid : TicketStatus::Refunded,
        ]);
    }

    foreach ($extras as [$total, $rate]) {
        $order->extras()->create([
            'description' => 'Drankkaart',
            'quantity' => 1,
            'unit_price_inc_vat' => $total,
            'vat_rate' => $rate,
            'line_total_inc_vat' => $total,
        ]);
    }

    return $order;
}

it('calculates ticket revenue excl. vat, ignoring refunded orders', function () {
    $event = Event::factory()->create();
    paidOrderFor($event, [[121, 21, 4]]);                                   // 100 excl.
    paidOrderFor($event, [[60.5, 21, 2]], status: OrderStatus::Refunded);   // telt niet

    $result = EventResult::for($event);

    expect($result->ticketRevenue)->toBe(100.0)
        ->and($result->visitors)->toBe(4);
});

it('spreads a discount code over the tickets only, not over the extras', function () {
    $event = Event::factory()->create();
    // Tickets 121 incl. (100 excl.), korting 60,50 incl. → tickets 50 excl.
    // Extra 12,10 incl. → 10 excl., ongemoeid door de korting.
    paidOrderFor($event, [[121, 21, 2]], [[12.10, 21]], discount: 60.5);

    expect(EventResult::for($event)->ticketRevenue)->toBe(60.0);
});

it('combines costs and other revenues into the result', function () {
    $event = Event::factory()->create();
    paidOrderFor($event, [[1210, 21, 100]]);                // 1000 excl., gem. 10/ticket
    $event->costs()->createMany([
        ['description' => 'DJ Carlos', 'amount' => 400],
        ['description' => 'Licht & geluid', 'amount' => 750],
    ]);
    $event->revenues()->create(['description' => 'Kassa', 'amount' => 250]);

    $result = EventResult::for($event->fresh());

    expect($result->revenue())->toBe(1250.0)
        ->and($result->costs)->toBe(1150.0)
        ->and($result->result())->toBe(100.0)
        ->and($result->margin())->toBe(8.0)
        ->and($result->resultPerVisitor())->toBe(1.0)
        ->and($result->breakEvenTickets())->toBe(90);    // (1150 − 250) / 10
});

it('saves costs and other revenues from the Resultaat tab', function () {
    $this->actingAs(admin());
    $event = Event::factory()->create();

    Livewire::test(EditEvent::class, ['record' => $event->getRouteKey()])
        ->fillForm([
            'costs' => [
                ['description' => 'Affiches', 'amount' => 85.5],
                ['description' => 'Facebook-advertenties', 'amount' => 120],
            ],
            'revenues' => [
                ['description' => 'Kassa', 'amount' => 300],
            ],
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $event->refresh();
    expect($event->costs()->count())->toBe(2)
        ->and((float) $event->costs()->sum('amount'))->toBe(205.5)
        ->and($event->revenues()->first()->description)->toBe('Kassa');
});

it('shows the results overview per year with totals', function () {
    $this->actingAs(admin());
    $event = Event::factory()->create(['name' => 'Fiesta Tropicana', 'start_date' => now()->startOfYear()->addDays(10)]);
    paidOrderFor($event, [[121, 21, 4]]);
    $event->costs()->create(['description' => 'DJ', 'amount' => 40]);
    Event::factory()->create(['name' => 'Vorig jaar', 'start_date' => now()->subYear()]);

    Livewire::test(EventResults::class)
        ->assertSee('Fiesta Tropicana')
        ->assertDontSee('Vorig jaar')
        ->assertSee('€ 60,00');
});
