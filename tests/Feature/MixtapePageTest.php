<?php

use App\Models\Mixtape;

/**
 * Publieke mixtape-detailpagina (/mixtapes/{slug}) — de deelbare link voor
 * social media en mails, met eigen OG-meta (cover) en hreflang per taal.
 */
it('renders a published mixtape with player, meta and og image', function () {
    $mixtape = Mixtape::create([
        'title' => 'Tropical Night',
        'subtitle' => 'Tropical House, Urban',
        'audio_url' => '/storage/website-audio/tropical.mp3',
        'cover_url' => '/storage/website-media/tropical.webp',
    ]);

    $response = $this->get("/mixtapes/{$mixtape->slug}")->assertOk();

    $response->assertSee('Tropical Night');
    $response->assertSee('/storage/website-audio/tropical.mp3');
    $response->assertSee('og:image', false);
    $response->assertSee('hreflang="es"', false);
});

it('generates a unique slug from the title on create', function () {
    $first = Mixtape::create(['title' => 'Summer Set', 'audio_url' => '/storage/a.mp3']);
    $second = Mixtape::create(['title' => 'Summer Set', 'audio_url' => '/storage/b.mp3']);

    expect($first->slug)->toBe('summer-set');
    expect($second->slug)->toBe('summer-set-2');
});

it('returns 404 for an unpublished mixtape for guests', function () {
    $mixtape = Mixtape::create([
        'title' => 'Verborgen',
        'audio_url' => '/storage/a.mp3',
        'published' => false,
    ]);

    $this->get("/mixtapes/{$mixtape->slug}")->assertNotFound();
});

it('serves the mixtape under a locale prefix with translated chrome', function () {
    $mixtape = Mixtape::create(['title' => 'Locale Set', 'audio_url' => '/storage/a.mp3']);

    $this->get("/en/mixtapes/{$mixtape->slug}")
        ->assertOk()
        ->assertSee('Locale Set');
});

it('lists published mixtapes in the sitemap', function () {
    $mixtape = Mixtape::create(['title' => 'Sitemap Set', 'audio_url' => '/storage/a.mp3']);

    $this->get('/sitemap.xml')
        ->assertOk()
        ->assertSee("/mixtapes/{$mixtape->slug}");
});
