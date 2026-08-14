<?php

/**
 * De kortingscode-validator controleert in strikte volgorde en telt gebruik
 * uitsluitend op BETAALDE bestellingen — een verlaten pending order mag een
 * code niet opgebruiken.
 */

use App\Models\DiscountCode;
use App\Models\Event;
use App\Models\TicketOrder;
use App\Services\DiscountCodeValidator;

function validateCode(string $code, ?Event $event = null, float $total = 30.0, int $qty = 2, string $email = 'koper@example.com'): array
{
    $event ??= Event::factory()->create();

    return app(DiscountCodeValidator::class)->validate($code, $email, $total, $qty, $event->id);
}

it('weigert een onbekende code', function () {
    expect(validateCode('BESTAATNIET'))
        ->valid->toBeFalse()
        ->error->toBe('Deze kortingscode bestaat niet.');
});

it('weigert een inactieve code', function () {
    DiscountCode::factory()->create(['code' => 'UIT', 'is_active' => false]);

    expect(validateCode('UIT'))->error->toBe('Deze kortingscode is niet meer actief.');
});

it('weigert een code buiten haar geldigheidsvenster', function () {
    DiscountCode::factory()->create(['code' => 'STRAKS', 'valid_from' => now()->addWeek()]);
    DiscountCode::factory()->create(['code' => 'VOORBIJ', 'valid_until' => now()->subWeek()]);

    expect(validateCode('STRAKS'))->error->toBe('Deze kortingscode is nog niet geldig.')
        ->and(validateCode('VOORBIJ'))->error->toBe('Deze kortingscode is verlopen.');
});

it('weigert een event-gebonden code op een ander event', function () {
    $bound = Event::factory()->create(['name' => 'Latin Night']);
    $other = Event::factory()->create();
    $code = DiscountCode::factory()->create(['code' => 'LATIN']);
    $code->events()->attach($bound);

    expect(validateCode('LATIN', $other))->error->toBe('Deze kortingscode is enkel geldig voor Latin Night.')
        ->and(validateCode('LATIN', $bound))->valid->toBeTrue();
});

it('weigert onder het minimale bestelbedrag', function () {
    DiscountCode::factory()->create(['code' => 'MINIMUM', 'min_order_amount' => 50]);

    expect(validateCode('MINIMUM', total: 30.0))->error->toBe('Deze kortingscode is enkel geldig vanaf een bestelbedrag van € 50,00.');
});

it('telt max_uses enkel op betaalde bestellingen', function () {
    $code = DiscountCode::factory()->create(['code' => 'EENMALIG', 'max_uses' => 1]);

    // Een pending order telt niet mee …
    TicketOrder::factory()->create(['discount_code_id' => $code->id]);
    expect(validateCode('EENMALIG'))->valid->toBeTrue();

    // … een betaalde wel.
    TicketOrder::factory()->paid()->create(['discount_code_id' => $code->id]);
    expect(validateCode('EENMALIG'))->error->toBe('Deze kortingscode is niet meer beschikbaar.');
});

it('beperkt gebruik per e-mailadres', function () {
    $code = DiscountCode::factory()->create(['code' => 'PERKOPER', 'max_uses_per_email' => 1]);
    TicketOrder::factory()->paid()->create([
        'discount_code_id' => $code->id,
        'buyer_email' => 'koper@example.com',
    ]);

    expect(validateCode('PERKOPER', email: 'koper@example.com'))->error->toBe('Je hebt deze kortingscode al gebruikt.')
        ->and(validateCode('PERKOPER', email: 'iemand-anders@example.com'))->valid->toBeTrue();
});

it('rekent percentage-, vast- en per-ticket-kortingen correct uit', function () {
    DiscountCode::factory()->create(['code' => 'TIEN', 'type' => 'percentage', 'value' => 10]);
    DiscountCode::factory()->fixed(5)->create(['code' => 'VIJFEURO']);
    DiscountCode::factory()->fixed(5, perTicket: true)->create(['code' => 'VIJFPERSTUK']);

    expect(validateCode('TIEN', total: 30.0)['discount_amount'])->toBe(3.0)
        ->and(validateCode('VIJFEURO', total: 30.0)['discount_amount'])->toBe(5.0)
        ->and(validateCode('VIJFPERSTUK', total: 30.0, qty: 2)['discount_amount'])->toBe(10.0);
});

it('plafonneert de korting op het bestelbedrag', function () {
    DiscountCode::factory()->fixed(100)->create(['code' => 'TEVEEL']);

    expect(validateCode('TEVEEL', total: 30.0)['discount_amount'])->toBe(30.0);
});

it('normaliseert invoer naar hoofdletters', function () {
    DiscountCode::factory()->create(['code' => 'zomer']);

    expect(validateCode('  zomer '))->valid->toBeTrue();
});
