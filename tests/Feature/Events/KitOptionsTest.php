<?php

/**
 * De formulier- en tag-dropdowns op Instellingen → E-mailmarketing laden hun
 * opties live uit het Kit-account (KitApi). Zo hoeft niemand een numeriek
 * Kit-ID op te zoeken — dat veld leek vroeger onbruikbaar ("ik kan niets
 * typen") omdat het een numeriek TextInput was.
 */

use App\Filament\Pages\EmailMarketingSettings;
use App\Models\Setting;
use App\Services\KitApi;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

beforeEach(fn () => Cache::flush());

it('returns no options without an api key', function () {
    Http::fake();

    expect(KitApi::formOptions())->toBe([])
        ->and(KitApi::tagOptions())->toBe([]);

    Http::assertNothingSent();
});

it('loads tag options from kit, alphabetically', function () {
    Setting::set('kit_api_key', 'kit-secret');

    Http::fake(['api.kit.com/v4/tags*' => Http::response([
        'tags' => [
            ['id' => 2, 'name' => 'Zomerfeest'],
            ['id' => 1, 'name' => 'ticketkopers'],
            ['id' => 3, 'name' => 'Bachata'],
        ],
        'pagination' => ['has_next_page' => false],
    ])]);

    // Alfabetisch, hoofdletterongevoelig — conventie voor alle dropdowns.
    expect(array_values(KitApi::tagOptions()))->toBe(['Bachata', 'ticketkopers', 'Zomerfeest']);
});

it('follows pagination when fetching options', function () {
    Setting::set('kit_api_key', 'kit-secret');

    Http::fake(['api.kit.com/v4/forms*' => Http::sequence()
        ->push([
            'forms' => [['id' => 1, 'name' => 'Nieuwsbrief']],
            'pagination' => ['has_next_page' => true, 'end_cursor' => 'abc'],
        ])
        ->push([
            'forms' => [['id' => 2, 'name' => 'Aftermovie']],
            'pagination' => ['has_next_page' => false],
        ]),
    ]);

    expect(KitApi::formOptions())->toBe([2 => 'Aftermovie', 1 => 'Nieuwsbrief']);
});

it('returns an empty list when kit is unreachable, without caching the failure', function () {
    Setting::set('kit_api_key', 'kit-secret');

    // Eerste call faalt (ongeldige key), daarna werkt de API wél: het lege
    // resultaat mag niet uit cache komen.
    Http::fake(['api.kit.com/v4/tags*' => Http::sequence()
        ->pushStatus(401)
        ->push([
            'tags' => [['id' => 1, 'name' => 'ticketkopers']],
            'pagination' => ['has_next_page' => false],
        ]),
    ]);

    expect(KitApi::tagOptions())->toBe([])
        ->and(KitApi::tagOptions())->toBe([1 => 'ticketkopers']);
});

it('creates a tag in kit and returns its id', function () {
    Setting::set('kit_api_key', 'kit-secret');

    Http::fake(['api.kit.com/v4/tags' => Http::response(['tag' => ['id' => 99, 'name' => 'ticketkopers']], 201)]);

    expect(KitApi::createTag('ticketkopers'))->toBe(99);

    Http::assertSent(fn ($request) => $request->url() === 'https://api.kit.com/v4/tags'
        && $request['name'] === 'ticketkopers'
        && $request->hasHeader('X-Kit-Api-Key', 'kit-secret'));
});

it('falls back to the existing tag id when the name already exists', function () {
    Setting::set('kit_api_key', 'kit-secret');

    // POST geeft 422 (naam bestaat al); de GET-lijst bevat de bestaande tag.
    Http::fake(function ($request) {
        if ($request->method() === 'POST') {
            return Http::response(['errors' => ['Name already exists']], 422);
        }

        return Http::response([
            'tags' => [['id' => 7, 'name' => 'ticketkopers']],
            'pagination' => ['has_next_page' => false],
        ]);
    });

    expect(KitApi::createTag('ticketkopers'))->toBe(7);
});

it('shows kit forms and tags as dropdown options in the admin', function () {
    Setting::set('kit_api_key', 'kit-secret');

    Http::fake([
        'api.kit.com/v4/forms*' => Http::response([
            'forms' => [['id' => 11, 'name' => 'Nieuwsbrief']],
            'pagination' => ['has_next_page' => false],
        ]),
        'api.kit.com/v4/tags*' => Http::response([
            'tags' => [['id' => 22, 'name' => 'ticketkopers']],
            'pagination' => ['has_next_page' => false],
        ]),
    ]);

    $this->actingAs(admin());

    Livewire::test(EmailMarketingSettings::class)
        ->assertSee('Nieuwsbrief')
        ->assertSee('ticketkopers');
});

it('keeps a saved id visible when it no longer exists in kit', function () {
    Setting::set('kit_api_key', 'kit-secret');
    Setting::set('kit_tag_id', '456');

    Http::fake(['api.kit.com/*' => Http::response([
        'forms' => [], 'tags' => [], 'pagination' => ['has_next_page' => false],
    ])]);

    $this->actingAs(admin());

    Livewire::test(EmailMarketingSettings::class)
        ->assertSee('Tag #456 (niet gevonden in Kit)');
});

it('still saves the selected ids to settings', function () {
    Setting::set('kit_api_key', 'kit-secret');

    Http::fake([
        'api.kit.com/v4/forms*' => Http::response([
            'forms' => [['id' => 11, 'name' => 'Nieuwsbrief']],
            'pagination' => ['has_next_page' => false],
        ]),
        'api.kit.com/v4/tags*' => Http::response([
            'tags' => [['id' => 22, 'name' => 'ticketkopers']],
            'pagination' => ['has_next_page' => false],
        ]),
    ]);

    $this->actingAs(admin());

    Livewire::test(EmailMarketingSettings::class)
        ->set('data.kit_form_id', 11)
        ->set('data.kit_tag_id', 22)
        ->call('save')
        ->assertNotified();

    expect(Setting::get('kit_form_id'))->toEqual(11)
        ->and(Setting::get('kit_tag_id'))->toEqual(22);
});
