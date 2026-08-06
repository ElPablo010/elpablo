<?php

use App\Models\Page;
use App\Models\User;

/**
 * De frontend toont voor ingelogde beheerders een snelkoppeling naar de admin,
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
        ->assertDontSee('Bewerk deze pagina');
});

it('hides the admin shortcut from logged-in non-admins', function () {
    publishedPage();

    // Staff mag niet op het Filament-paneel (zie User::canAccessPanel), dus ook
    // de snelkoppeling ernaartoe hoort verborgen te blijven.
    $this->actingAs(User::factory()->create(['role' => App\Enums\UserRole::Staff]))
        ->get('/')
        ->assertOk()
        ->assertDontSee('Bewerk deze pagina');
});

it('shows the admin shortcut linking to the edit screen of the current page', function () {
    $page = publishedPage();

    $this->actingAs(admin())
        ->get('/')
        ->assertOk()
        ->assertSee('Bewerk deze pagina')
        ->assertSee(App\Filament\Resources\Pages\PageResource::getUrl('edit', ['record' => $page]), escape: false);
});

it('falls back to the page overview when there is no page (404)', function () {
    $this->actingAs(admin())
        ->get('/bestaat-niet')
        ->assertNotFound()
        ->assertSee('Naar de admin')
        ->assertSee(App\Filament\Resources\Pages\PageResource::getUrl('index'), escape: false);
});
