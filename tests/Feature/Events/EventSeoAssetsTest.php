<?php

/**
 * De SEO/GEO-assets moeten de events meenemen: sitemap.xml (met hreflang-
 * alternates en x-default) en llms.txt (het events-overzicht voor AI-zoekers).
 */

use App\Models\Event;

it('includes the events index and detail URLs in the sitemap', function () {
    Event::factory()->create(['slug' => 'latin-night', 'name' => 'Latin Night']);

    $xml = $this->get('/sitemap.xml')->assertOk()->getContent();

    expect($xml)->toContain('/events</loc>')
        ->toContain('/events/latin-night</loc>');
});

it('omits draft events from the sitemap', function () {
    Event::factory()->draft()->create(['slug' => 'geheim-event']);

    $xml = $this->get('/sitemap.xml')->getContent();

    expect($xml)->not->toContain('geheim-event');
});

it('lists upcoming events in llms.txt with their date', function () {
    Event::factory()->create([
        'slug' => 'latin-night',
        'name' => 'Latin Night',
        'start_date' => '2026-09-19',
    ]);

    $txt = $this->get('/llms.txt')->assertOk()->getContent();

    expect($txt)->toContain('## Events')
        ->toContain('Latin Night (19/09/2026)')
        ->toContain('/events/latin-night');
});

it('omits past and cancelled events from llms.txt', function () {
    Event::factory()->create(['slug' => 'voorbij', 'name' => 'Voorbij Event', 'start_date' => now()->subMonth()->toDateString()]);
    Event::factory()->cancelled()->create(['slug' => 'afgelast-event', 'name' => 'Afgelast Event']);

    $txt = $this->get('/llms.txt')->getContent();

    expect($txt)->not->toContain('Voorbij Event')
        ->not->toContain('Afgelast Event');
});
