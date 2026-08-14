<?php

/**
 * De prijskern (Event::lineTotalFor / currentPriceFor) is de enige bron van
 * waarheid voor wat een regel tickets kost. Deze tests pinnen het gedrag vast
 * dat uit bailando-latino geport is: laagste regeltotaal wint bij overlappende
 * promo's, BOGO-gratis-tickets worden over de stuksprijs uitgesmeerd, en
 * ex-BTW wordt altijd afgeleid uit het tarief op de event↔tickettype-pivot.
 */

use App\Models\Event;
use App\Models\EventTicketDiscount;
use App\Models\EventTicketType;
use App\Models\TicketType;

function eventWithTicketType(float $price = 15, float $vatRate = 21): array
{
    $event = Event::factory()->create();
    $type = TicketType::factory()->create();
    EventTicketType::factory()->create([
        'event_id' => $event->id,
        'ticket_type_id' => $type->id,
        'price' => $price,
        'vat_rate' => $vatRate,
    ]);

    return [$event->fresh(), $type];
}

it('rekent zonder promo de reguliere prijs maal het aantal', function () {
    [$event, $type] = eventWithTicketType(price: 15);

    $line = $event->lineTotalFor($type->id, 2);

    expect($line['total_inc_vat'])->toBe(30.0)
        ->and($line['unit_inc_vat'])->toBe(15.0)
        ->and($line['free'])->toBe(0)
        ->and($line['charged'])->toBe(2)
        ->and($line['discount'])->toBeNull();
});

it('past een actieve vaste-prijs-promo toe', function () {
    [$event, $type] = eventWithTicketType(price: 15);
    EventTicketDiscount::factory()->create([
        'event_id' => $event->id,
        'ticket_type_id' => $type->id,
        'price' => 10,
    ]);

    $line = $event->fresh()->lineTotalFor($type->id, 2);

    expect($line['total_inc_vat'])->toBe(20.0)
        ->and($line['unit_inc_vat'])->toBe(10.0)
        ->and($line['discount']['name'])->toBe('Early bird');
});

it('negeert een promo buiten haar datumvenster', function () {
    [$event, $type] = eventWithTicketType(price: 15);
    EventTicketDiscount::factory()->create([
        'event_id' => $event->id,
        'ticket_type_id' => $type->id,
        'price' => 10,
        'valid_from' => now()->subMonth()->toDateString(),
        'valid_until' => now()->subWeek()->toDateString(),
    ]);

    $line = $event->fresh()->lineTotalFor($type->id, 2);

    expect($line['total_inc_vat'])->toBe(30.0)
        ->and($line['discount'])->toBeNull();
});

it('rekent koop-X-plus-Y-gratis correct over verschillende aantallen', function (int $qty, int $expectedFree, float $expectedTotal) {
    [$event, $type] = eventWithTicketType(price: 15);
    EventTicketDiscount::factory()->buyXGetY(3, 1)->create([
        'event_id' => $event->id,
        'ticket_type_id' => $type->id,
    ]);

    $line = $event->fresh()->lineTotalFor($type->id, $qty);

    expect($line['free'])->toBe($expectedFree)
        ->and($line['total_inc_vat'])->toBe($expectedTotal);
})->with([
    'onder de drempel' => [1, 0, 15.0],
    'net onder een groep' => [3, 0, 45.0],
    'exact één groep' => [4, 1, 45.0],
    'één groep plus rest' => [5, 1, 60.0],
    'twee groepen' => [8, 2, 90.0],
]);

it('laat bij overlappende promos het laagste regeltotaal winnen', function () {
    [$event, $type] = eventWithTicketType(price: 15);
    EventTicketDiscount::factory()->create([
        'event_id' => $event->id,
        'ticket_type_id' => $type->id,
        'name' => 'Vaste promo',
        'price' => 12,
    ]);
    EventTicketDiscount::factory()->buyXGetY(3, 1)->create([
        'event_id' => $event->id,
        'ticket_type_id' => $type->id,
    ]);

    $event = $event->fresh();

    // Bij 4 stuks: vaste prijs = 48, BOGO = 45 → BOGO wint.
    $bij4 = $event->lineTotalFor($type->id, 4);
    expect($bij4['total_inc_vat'])->toBe(45.0)
        ->and($bij4['discount']['type'])->toBe('buy_x_get_y');

    // Bij 2 stuks: vaste prijs = 24, BOGO = 30 → vaste prijs wint.
    $bij2 = $event->lineTotalFor($type->id, 2);
    expect($bij2['total_inc_vat'])->toBe(24.0)
        ->and($bij2['discount']['type'])->toBe('fixed_price');
});

it('smeert het BOGO-voordeel uit over de stuksprijs zodat stuks maal aantal exact klopt', function () {
    [$event, $type] = eventWithTicketType(price: 15);
    EventTicketDiscount::factory()->buyXGetY(3, 1)->create([
        'event_id' => $event->id,
        'ticket_type_id' => $type->id,
    ]);

    $line = $event->fresh()->lineTotalFor($type->id, 4);

    expect($line['unit_inc_vat'])->toBe(11.25)
        ->and(round($line['unit_inc_vat'] * $line['quantity'], 2))->toBe($line['total_inc_vat']);
});

it('leidt ex-BTW af uit het tarief op de pivot', function () {
    [$event, $type] = eventWithTicketType(price: 15, vatRate: 6);

    $line = $event->lineTotalFor($type->id, 2);

    expect($line['vat_rate'])->toBe(6.0)
        ->and($line['total_ex_vat'])->toBe(round(30 / 1.06, 2));
});

it('toont in currentPriceFor enkel vaste-prijs-promos, geen BOGO', function () {
    [$event, $type] = eventWithTicketType(price: 15);
    EventTicketDiscount::factory()->buyXGetY(3, 1)->create([
        'event_id' => $event->id,
        'ticket_type_id' => $type->id,
    ]);

    $price = $event->fresh()->currentPriceFor($type->id);

    expect($price['current'])->toBe(15.0)
        ->and($price['discount'])->toBeNull();
});
