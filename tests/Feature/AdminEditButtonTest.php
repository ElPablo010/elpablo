<?php

use Webgoeroe\Core\Enums\UserRole;
use App\Models\Page;
use App\Models\User;
use Webgoeroe\Core\Filament\Resources\Pages\PageResource;

/**
 * De frontend toont voor ingelogde beheerders de bewerkknop van de core (eigen stijl),
 * net onder de menubalk. Voor bezoekers mag daar niets van te zien zijn.
 */
function publishedPage(): Page
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

it('hides the admin shortcut from guests', function () {
    publishedPage();

    $this->get('/')
        ->assertOk()
        ->assertDontSee('Pagina bewerken');
});

it('shows the admin shortcut to a Medewerker too', function () {
    publishedPage();

    // Elke rol mag op het Filament-paneel (zie HasRoles::canAccessPanel) en
    // pagina's bewerken, dus ook een Medewerker krijgt de snelkoppeling.
    $this->actingAs(User::factory()->create(['role' => UserRole::Staff]))
        ->get('/')
        ->assertOk()
        ->assertSee('Pagina bewerken');
});

it('shows the admin shortcut linking to the edit screen of the current page', function () {
    $page = publishedPage();

    $this->actingAs(admin())
        ->get('/')
        ->assertOk()
        ->assertSee('Pagina bewerken')
        ->assertSee(PageResource::getUrl('edit', ['record' => $page]), escape: false);
});

it('falls back to the admin when there is no page (404)', function () {
    $this->actingAs(admin())
        ->get('/bestaat-niet')
        ->assertNotFound()
        ->assertSee('Naar de admin')
        ->assertSee(url('/admin'), escape: false);
});

it('links an event to its own edit screen', function () {
    $this->actingAs(admin())
        ->get('/events')
        ->assertOk()
        ->assertSee('Bewerk in de admin')
        ->assertSee(\App\Filament\Resources\Events\EventResource::getUrl('index'), escape: false);
});
