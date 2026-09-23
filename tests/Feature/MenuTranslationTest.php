<?php

use App\Enums\UserRole;
use App\Filament\Pages\ManageMenus;
use App\Filament\Resources\Events\EventResource;
use App\Filament\Schemas\Components\PageLinkField;
use App\Models\Event;
use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\Page;
use App\Models\User;
use App\Services\Translation\ClaudeTranslator;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

/**
 * Menu's zijn één set items voor alle talen; enkel labels en footertitels
 * verschillen per taal (label_en/label_es, title_en/title_es).
 */
beforeEach(fn () => actingAs(User::factory()->create(['role' => UserRole::Admin])));

it('toont het label in de taal van de bezoeker, met terugval', function () {
    $menu = Menu::create(['location' => 'main', 'name' => 'Hoofdmenu']);
    $item = MenuItem::create(['menu_id' => $menu->id, 'label' => 'Boeken', 'label_en' => 'Book now', 'url' => '/boeken']);
    $bare = MenuItem::create(['menu_id' => $menu->id, 'label' => 'Iets zonder vertaling', 'url' => '/x']);

    expect($item->labelFor('nl'))->toBe('Boeken')
        ->and($item->labelFor('en'))->toBe('Book now')
        // Geen ES-kolom → lang/es.json → NL.
        ->and($item->labelFor('es'))->toBe(__('Boeken', locale: 'es'))
        ->and($bare->labelFor('en'))->toBe('Iets zonder vertaling');
});

it('vertaalt de menu\'s met AI in het formulier en bewaart ze bij opslaan', function () {
    test()->swap(ClaudeTranslator::class, new class extends ClaudeTranslator
    {
        public function translate(array $texts, string $fromLocale, string $toLocale, ?string $context = null): array
        {
            return array_map(fn (string $text) => strtoupper($toLocale).': '.$text, $texts);
        }
    });

    $main = Menu::create(['location' => 'main', 'name' => 'Hoofdmenu']);
    $parent = MenuItem::create(['menu_id' => $main->id, 'label' => 'Muziek', 'url' => '/muziek']);
    MenuItem::create(['menu_id' => $main->id, 'parent_id' => $parent->id, 'label' => 'Mixtapes', 'url' => '/mixtapes']);
    Menu::create(['location' => 'footer_1', 'name' => 'Footer menu 1', 'title' => 'Ontdekken']);

    Livewire::test(ManageMenus::class)
        ->call('translateWithAi')
        ->call('save');

    $parent = MenuItem::where('label', 'Muziek')->first();
    $child = MenuItem::where('label', 'Mixtapes')->first();

    expect($parent->label_en)->toBe('EN: Muziek')
        ->and($parent->label_es)->toBe('ES: Muziek')
        ->and($child->label_en)->toBe('EN: Mixtapes')
        ->and(Menu::where('location', 'footer_1')->first()->title_es)->toBe('ES: Ontdekken');
});

it('toont de vertaalde labels op de Engelse site', function () {
    $main = Menu::create(['location' => 'main', 'name' => 'Hoofdmenu']);
    MenuItem::create(['menu_id' => $main->id, 'label' => 'Agenda', 'label_en' => 'Upcoming gigs', 'url' => '/events']);

    Page::create(['title' => 'Home', 'slug' => 'home', 'locale' => 'nl', 'is_homepage' => true, 'published' => true]);
    Page::create(['title' => 'Home', 'slug' => 'home', 'locale' => 'en', 'is_homepage' => true, 'published' => true]);

    get('/en')->assertOk()->assertSee('Upcoming gigs');
});

it('biedt in de paginakeuze enkel de NL-pagina\'s aan', function () {
    $nl = Page::create(['title' => 'Boeken', 'slug' => 'boeken', 'locale' => 'nl', 'published' => true]);
    Page::create(['title' => 'Book now', 'slug' => 'boeken', 'locale' => 'en', 'translation_of' => $nl->id, 'published' => true]);

    expect(PageLinkField::pageOptions())->toBe([$nl->id => 'Boeken']);
});

it('stuurt de bewerkknop op een eventpagina naar dat event in de admin', function () {
    $event = Event::create([
        'slug' => 'fiesta',
        'name' => 'Fiesta',
        'start_date' => now()->addMonth()->toDateString(),
        'published' => true,
    ]);

    get('/events/fiesta')
        ->assertOk()
        ->assertSee(EventResource::getUrl('edit', ['record' => $event]), false)
        ->assertSee('id="tickets"', false);
});
