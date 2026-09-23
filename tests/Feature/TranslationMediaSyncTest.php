<?php

use App\Enums\UserRole;
use App\Filament\Resources\Pages\Pages\EditPage;
use App\Models\Page;
use App\Models\User;
use App\Services\Translation\TranslationMediaSync;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\artisan;

/**
 * Media zijn taalloos: een foto die je in het NL vervangt of toevoegt, moet
 * ook in de EN/ES-vertaling verschijnen — zonder de vertaalde tekst te raken.
 */
function pageWithTranslation(): array
{
    $nl = Page::create(['title' => 'Over', 'slug' => 'over', 'locale' => 'nl', 'published' => true]);
    $nl->sections()->create([
        'section_type' => 'text_media',
        'position' => 0,
        'content' => [
            'heading' => 'Wie is El Pablo',
            'images' => [['src' => '/storage/oud.jpg', 'alt' => 'DJ achter de draaitafels']],
        ],
    ]);

    $en = Page::create(['title' => 'About', 'slug' => 'over', 'locale' => 'en', 'translation_of' => $nl->id, 'published' => true]);
    $en->sections()->create([
        'section_type' => 'text_media',
        'position' => 0,
        'locale' => 'en',
        'content' => [
            'heading' => 'Who is El Pablo',
            'images' => [['src' => '/storage/oud.jpg', 'alt' => 'DJ behind the decks']],
        ],
    ]);

    return [$nl, $en];
}

it('neemt een vervangen en een toegevoegde foto over zonder de tekst te raken', function () {
    [$nl, $en] = pageWithTranslation();

    $nl->sections()->first()->update(['content' => [
        'heading' => 'Wie is El Pablo',
        'images' => [
            ['src' => '/storage/nieuw.jpg', 'alt' => 'DJ achter de draaitafels'],
            ['src' => '/storage/extra.jpg', 'alt' => 'Publiek'],
        ],
    ]]);

    app(TranslationMediaSync::class)->sync($nl->fresh());

    $content = $en->fresh()->sections->first()->content;

    expect($content['images'][0]['src'])->toBe('/storage/nieuw.jpg')
        ->and($content['images'][0]['alt'])->toBe('DJ behind the decks')
        ->and($content['heading'])->toBe('Who is El Pablo')
        ->and($content['images'][1]['src'])->toBe('/storage/extra.jpg');
});

it('synchroniseert bij het opslaan van de NL-pagina in de admin', function () {
    actingAs(User::factory()->create(['role' => UserRole::Admin]));
    [$nl, $en] = pageWithTranslation();

    // Simuleer een bewerking in de admin: de sectie krijgt een nieuwe foto.
    $nl->sections()->first()->update(['content' => [
        'heading' => 'Wie is El Pablo',
        'media_type' => 'images',
        'media_side' => 'right',
        'images' => [['src' => '/storage/nieuw.jpg', 'alt' => 'DJ achter de draaitafels']],
    ]]);

    Livewire::test(EditPage::class, ['record' => $nl->getRouteKey()])
        ->call('save')
        ->assertHasNoErrors();

    expect($en->fresh()->sections->first()->content['images'][0]['src'])->toBe('/storage/nieuw.jpg');
});

it('slaat een sectie over als de opbouw van de vertaling afwijkt', function () {
    [$nl, $en] = pageWithTranslation();
    $en->sections()->first()->update(['section_type' => 'hero']);

    $nl->sections()->first()->update(['content' => ['images' => [['src' => '/storage/nieuw.jpg']]]]);
    app(TranslationMediaSync::class)->sync($nl->fresh());

    expect($en->fresh()->sections->first()->content['images'][0]['src'])->toBe('/storage/oud.jpg');
});

it('trekt bestaande pagina\'s gelijk via het commando', function () {
    [$nl, $en] = pageWithTranslation();
    $nl->sections()->first()->update(['content' => ['images' => [['src' => '/storage/nieuw.jpg']]]]);

    artisan('pages:sync-translation-media')->assertSuccessful();

    expect($en->fresh()->sections->first()->content['images'][0]['src'])->toBe('/storage/nieuw.jpg');
});
