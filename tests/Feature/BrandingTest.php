<?php

use App\Models\Page;
use App\Models\Setting;
use App\Support\SiteFooter;
use App\Support\SiteHeader;

/**
 * Merk-chrome op de publieke site: het beeldmerk met de naam ernaast (header én
 * footer) en het favicon in de <head>.
 */
function brandedPage(): Page
{
    $page = Page::create([
        'title' => 'Welkom',
        'slug' => 'home',
        'is_homepage' => true,
        'published' => true,
    ]);
    $page->sections()->create([
        'section_type' => 'hero',
        'position' => 0,
        'content' => ['heading' => 'Titel'],
    ]);

    return $page;
}

it('shows the logo with the brand name next to it', function () {
    brandedPage();
    Setting::set(SiteHeader::KEY, ['logo' => '/storage/website-media/logo.webp', 'name' => 'El Pablo', 'subtitle' => 'Urban Latin DJ']);
    Setting::set(SiteFooter::KEY, ['brand' => ['logo' => '/storage/website-media/logo.webp', 'name' => 'El Pablo']]);

    $html = $this->get('/')->assertOk()->getContent();

    // Header: logo-afbeelding (h-9) mét de naam en ondertitel ernaast.
    expect($html)->toContain('<img src="/storage/website-media/logo.webp" alt="El Pablo" class="h-9 w-auto">')
        ->toContain('<span class="font-display text-2xl tracking-tight">El Pablo</span>')
        ->toContain('Urban Latin DJ')
        // Footer: idem, met het grotere logo.
        ->toContain('<img src="/storage/website-media/logo.webp" alt="El Pablo" class="h-10 w-auto">')
        ->toContain('<span class="font-display text-3xl tracking-tight">El Pablo</span>');
});

it('hides the brand name when the logo already contains it', function () {
    brandedPage();
    Setting::set(SiteHeader::KEY, [
        'logo' => '/storage/website-media/logo.webp',
        'name' => 'Merknaam In Logo',
        'show_name' => false,
    ]);

    $this->get('/')
        ->assertOk()
        ->assertSee('/storage/website-media/logo.webp', escape: false)
        // Enkel nog als alt-tekst van het logo, niet als zichtbare kop.
        ->assertDontSee('<span class="font-display text-2xl', escape: false);
});

it('still shows the name when no logo is set', function () {
    brandedPage();
    Setting::set(SiteHeader::KEY, ['name' => 'El Pablo', 'show_name' => false]);

    $this->get('/')->assertOk()->assertSee('El Pablo');
});

it('links the bundled favicon when none is configured', function () {
    brandedPage();

    $this->get('/')
        ->assertOk()
        ->assertSee('<link rel="icon" href="/favicon.ico?v=2"', escape: false)
        ->assertSee('/favicon.svg?v=2', escape: false)
        ->assertSee('/apple-touch-icon.png?v=2', escape: false);
});

it('prefers a favicon uploaded in the admin', function () {
    brandedPage();
    Setting::set(SiteHeader::KEY, ['favicon' => '/storage/website-media/icon.webp']);

    $this->get('/')
        ->assertOk()
        ->assertSee('<link rel="icon" href="/storage/website-media/icon.webp">', escape: false)
        ->assertDontSee('/favicon.ico?v=2', escape: false);
});
