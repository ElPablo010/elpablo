<?php

/**
 * De Events-admin moet volledig door zijn eigen formulieren komen: een event
 * met tickettypes, promo's en vertalingen aanmaken én ongewijzigd heropslaan.
 * Vangt formulier-blokkades (verplichte velden op onzichtbare tabs, kapotte
 * repeater-relaties) vóór de beheerder ze vindt.
 */

use App\Enums\TicketDiscountType;
use App\Filament\Pages\PaymentSettings;
use App\Filament\Resources\Events\Pages\CreateEvent;
use App\Filament\Resources\Events\Pages\EditEvent;
use App\Models\Event;
use App\Models\EventTicketDiscount;
use App\Models\EventTicketType;
use App\Models\Setting;
use App\Models\TicketType;
use Livewire\Livewire;

it('creates an event with tickets, promos and translations from the admin', function () {
    $this->actingAs(admin());
    $type = TicketType::factory()->create(['name' => 'Standaard']);

    Livewire::test(CreateEvent::class)
        ->fillForm([
            'name' => 'Latin Night',
            'slug' => 'latin-night',
            'start_date' => now()->addMonth()->toDateString(),
            'published' => true,
            'is_cancelled' => false,
            'eventTicketTypes' => [
                ['ticket_type_id' => $type->id, 'price' => 15, 'vat_rate' => 21, 'capacity' => 100],
            ],
            'ticketDiscounts' => [
                [
                    'ticket_type_id' => $type->id,
                    'name' => 'Early bird',
                    'type' => TicketDiscountType::FixedPrice->value,
                    'price' => 10,
                    'valid_from' => now()->toDateString(),
                    'valid_until' => now()->addWeek()->toDateString(),
                ],
            ],
            'translations' => [
                'en' => ['name' => 'Latin Night (EN)', 'short_description' => null, 'description' => null],
                'es' => ['name' => null, 'short_description' => null, 'description' => null],
            ],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $event = Event::where('slug', 'latin-night')->firstOrFail();

    expect($event->published)->toBeTrue()
        ->and(EventTicketType::where('event_id', $event->id)->count())->toBe(1)
        ->and(EventTicketDiscount::where('event_id', $event->id)->count())->toBe(1)
        ->and($event->translationFor('en')?->name)->toBe('Latin Night (EN)')
        ->and($event->translationFor('es')?->hasContent())->toBeFalse();
});

it('saves an existing event unchanged without validation errors', function () {
    $this->actingAs(admin());
    $event = Event::factory()->create();
    $type = TicketType::factory()->create();
    EventTicketType::factory()->create(['event_id' => $event->id, 'ticket_type_id' => $type->id]);
    $event->translations()->create(['locale' => 'en', 'name' => 'English name']);

    Livewire::test(EditEvent::class, ['record' => $event->getRouteKey()])
        ->call('save')
        ->assertHasNoFormErrors();

    // Heropslaan mag vertalingen niet wissen of dupliceren.
    expect($event->fresh()->translationFor('en')?->name)->toBe('English name')
        ->and($event->fresh()->translations()->count())->toBe(2); // en + es (lege placeholder)
});

it('cancels and un-cancels an event via the toggle and keeps the message', function () {
    $this->actingAs(admin());
    $event = Event::factory()->create();

    Livewire::test(EditEvent::class, ['record' => $event->getRouteKey()])
        ->fillForm(['is_cancelled' => true, 'cancellation_message' => 'Afgelast wegens storm.'])
        ->call('save')
        ->assertHasNoFormErrors();

    $event->refresh();
    expect($event->isCancelled())->toBeTrue()
        ->and($event->cancellation_message)->toBe('Afgelast wegens storm.');

    Livewire::test(EditEvent::class, ['record' => $event->getRouteKey()])
        ->fillForm(['is_cancelled' => false])
        ->call('save')
        ->assertHasNoFormErrors();

    $event->refresh();
    // De boodschap blijft bewust staan voor een eventuele her-annulering.
    expect($event->isCancelled())->toBeFalse()
        ->and($event->cancellation_message)->toBe('Afgelast wegens storm.');
});

it('saves the payment settings page', function () {
    $this->actingAs(admin());

    Livewire::test(PaymentSettings::class)
        ->fillForm([
            'stripe_secret' => 'sk_test_abc',
            'stripe_webhook_secret' => 'whsec_abc',
            'admin_notification_email' => 'pieter@dewebgoeroe.be',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect(Setting::get('stripe_secret'))->toBe('sk_test_abc')
        ->and(Setting::get('stripe_webhook_secret'))->toBe('whsec_abc');
});
