<?php

/**
 * De publieke eventpagina's: overzicht en detail in de drie talen, drafts
 * verborgen, afgelast-banner, schema.org Event-JSON-LD en hreflang-alternates
 * die de hasContent()-guard van vertalingen respecteren.
 */

use App\Models\Event;
use App\Models\EventTicketType;
use App\Models\TicketType;

function publishedEvent(array $attributes = []): Event
{
    $event = Event::factory()->create($attributes + ['name' => 'Latin Night', 'slug' => 'latin-night']);
    $type = TicketType::factory()->create(['name' => 'Standaard']);
    EventTicketType::factory()->create([
        'event_id' => $event->id,
        'ticket_type_id' => $type->id,
        'price' => 15,
    ]);

    return $event->fresh();
}

it('shows the events index with upcoming events', function () {
    publishedEvent();

    $this->get('/events')
        ->assertOk()
        ->assertSee('Latin Night')
        ->assertSee('/events/latin-night');
});

it('renders the localized index and detail pages', function (string $prefix) {
    publishedEvent();

    $this->get("{$prefix}/events")->assertOk();
    $this->get("{$prefix}/events/latin-night")->assertOk()->assertSee('Latin Night');
})->with(['en' => ['/en'], 'es' => ['/es']]);

it('falls back to Dutch content and uses the translation when it has content', function () {
    $event = publishedEvent();
    $event->translations()->create(['locale' => 'en', 'name' => 'Latin Night London Edition']);

    $this->get('/en/events/latin-night')->assertSee('Latin Night London Edition');
    // ES heeft geen vertaling: NL-naam als fallback.
    $this->get('/es/events/latin-night')->assertSee('Latin Night');
});

it('hides draft events from guests but shows them to logged-in users', function () {
    publishedEvent(['published' => false]);

    $this->get('/events/latin-night')->assertNotFound();
    $this->get('/events')->assertOk()->assertDontSee('Latin Night');

    $this->actingAs(admin());
    $this->get('/events/latin-night')->assertOk();
});

it('shows a cancellation banner and keeps the event visible', function () {
    publishedEvent(['cancelled_at' => now(), 'cancellation_message' => 'Afgelast wegens storm.']);

    $this->get('/events/latin-night')
        ->assertOk()
        ->assertSee('Dit event is afgelast.')
        ->assertSee('Afgelast wegens storm.');
});

it('renders schema.org Event and Offer JSON-LD on the detail page', function () {
    publishedEvent();

    $html = $this->get('/events/latin-night')->assertOk()->getContent();

    expect($html)->toContain('"@type":"Event"')
        ->toContain('"@type":"Offer"')
        ->toContain('"priceCurrency":"EUR"')
        ->toContain('"eventStatus":"https://schema.org/EventScheduled"');
});

it('marks a cancelled event as EventCancelled in JSON-LD', function () {
    publishedEvent(['cancelled_at' => now()]);

    $html = $this->get('/events/latin-night')->getContent();

    expect($html)->toContain('"eventStatus":"https://schema.org/EventCancelled"');
});

it('only lists hreflang alternates for translations with content', function () {
    $event = publishedEvent();
    $event->translations()->create(['locale' => 'en', 'name' => 'Latin Night (EN)']);
    $event->translations()->create(['locale' => 'es']); // lege placeholder

    $html = $this->get('/events/latin-night')->getContent();

    expect($html)->toContain('hreflang="nl"')
        ->toContain('hreflang="en"')
        ->not->toContain('hreflang="es"');
});

it('serves the public ticket status page and 404s for unknown tokens', function () {
    $event = publishedEvent();
    $ticket = \App\Models\EventTicket::factory()->create([
        'event_id' => $event->id,
        'ticket_type_id' => $event->ticketTypes->first()->id,
    ]);

    $this->get('/t/'.$ticket->token)->assertOk()->assertSee('Latin Night');
    $this->get('/t/00000000000000000000000000')->assertNotFound();
});

it('links the language switcher to the same event path per locale', function () {
    publishedEvent();

    // Overzicht: NL → /en/events en /es/events; EN terug naar /events.
    $this->get('/events')->assertOk()
        ->assertSee('href="/en/events"', false)
        ->assertSee('href="/es/events"', false);
    $this->get('/en/events')->assertOk()
        ->assertSee('href="/events"', false)
        ->assertSee('href="/es/events"', false);

    // Detailpagina: gedeelde slug per taal.
    $this->get('/es/events/latin-night')->assertOk()
        ->assertSee('href="/events/latin-night"', false)
        ->assertSee('href="/en/events/latin-night"', false);
});

it('sends the language switcher home on paths without a locale variant', function () {
    $event = publishedEvent();
    $ticket = \App\Models\EventTicket::factory()->create([
        'event_id' => $event->id,
        'ticket_type_id' => $event->ticketTypes->first()->id,
    ]);

    $this->get('/t/'.$ticket->token)->assertOk()
        ->assertSee('href="/en"', false)
        ->assertDontSee('href="/en/t/', false);
});

it('still resolves CMS pages through the catch-all', function () {
    $page = \App\Models\Page::create([
        'title' => 'Over', 'slug' => 'over', 'locale' => 'nl', 'published' => true,
    ]);

    $this->get('/over')->assertOk();
});
