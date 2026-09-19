<?php

use App\Models\Menu;
use App\Models\Page;

/**
 * "Events" staat in het hoofdmenu en de footernavigatie, in elke taal, en wijst
 * naar de gelokaliseerde /events-route.
 */
function menuPage(string $locale = 'nl'): Page
{
    $page = Page::create([
        'title' => 'Welkom',
        'slug' => 'home',
        'locale' => $locale,
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

function seedEventsMenus(): void
{
    foreach (['main' => 'Hoofdmenu', 'footer_1' => 'Footer navigatie'] as $location => $name) {
        $menu = Menu::create(['location' => $location, 'name' => $name]);
        $menu->allItems()->create(['label' => 'Events', 'url' => '/events', 'position' => 0]);
        $menu->allItems()->create(['label' => 'Contact', 'url' => '/contact', 'position' => 1]);
    }
}

it('links to the events page from the header and footer', function () {
    menuPage();
    seedEventsMenus();

    $html = $this->get('/')->assertOk()->getContent();

    expect(substr_count($html, 'href="/events"'))->toBeGreaterThanOrEqual(2)
        ->and($html)->toContain('>Events</a>');
});

it('localises the events link and its label', function (string $locale, string $label) {
    menuPage($locale);
    seedEventsMenus();

    $html = $this->get('/'.$locale)->assertOk()->getContent();

    expect($html)->toContain('href="/'.$locale.'/events"')
        ->toContain('>'.$label.'</a>')
        ->not->toContain('href="/events"');
})->with([
    ['en', 'Events'],
    ['es', 'Eventos'],
]);
