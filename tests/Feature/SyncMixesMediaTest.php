<?php

use App\Models\Page;

/**
 * Elke taal is een eigen pagina met eigen secties, dus een mp3 die in NL wordt
 * geüpload komt niet vanzelf op /en en /es. Zonder deze sync bleven de vertalingen
 * naar de oude WordPress-uploads wijzen — die na de go-live verdwijnen.
 */
function mixesPage(string $locale, string $slug, array $items, ?int $translationOf = null): Page
{
    $page = Page::create([
        'title' => ucfirst($slug),
        'slug' => $slug,
        'locale' => $locale,
        'translation_of' => $translationOf,
        'published' => true,
    ]);

    $page->sections()->create([
        'section_type' => 'mixes',
        'position' => 0,
        'locale' => $locale,
        'content' => ['heading' => 'Sets', 'items' => $items],
    ]);

    return $page;
}

it('copies audio and cover from Dutch to the translations', function () {
    $nl = mixesPage('nl', 'muziek', [
        ['title' => 'Latin Vibes', 'subtitle' => 'Reggaeton', 'audio' => '/storage/website-audio/echt.mp3', 'cover' => '/storage/website-media/echt.webp'],
    ]);
    mixesPage('en', 'muziek', [
        ['title' => 'Latin Vibes', 'subtitle' => 'Reggaeton house', 'audio' => 'https://www.el-pablo.com/wp-content/uploads/2025/05/oud.mp3', 'cover' => 'https://images.unsplash.com/foo'],
    ], $nl->id);

    $this->artisan('mixes:sync-media')->assertSuccessful();

    $items = Page::where('locale', 'en')->where('slug', 'muziek')->first()
        ->sections->firstWhere('section_type', 'mixes')->content['items'];

    expect($items[0]['audio'])->toBe('/storage/website-audio/echt.mp3');
    expect($items[0]['cover'])->toBe('/storage/website-media/echt.webp');
});

it('keeps the translated subtitle', function () {
    $nl = mixesPage('nl', 'muziek', [
        ['title' => 'Latin Vibes', 'subtitle' => 'Live opname', 'audio' => '/storage/a.mp3'],
    ]);
    mixesPage('es', 'muziek', [
        ['title' => 'Latin Vibes', 'subtitle' => 'Grabación en directo', 'audio' => 'https://oud/x.mp3'],
    ], $nl->id);

    $this->artisan('mixes:sync-media')->assertSuccessful();

    $items = Page::where('locale', 'es')->where('slug', 'muziek')->first()
        ->sections->firstWhere('section_type', 'mixes')->content['items'];

    expect($items[0]['subtitle'])->toBe('Grabación en directo');
    expect($items[0]['audio'])->toBe('/storage/a.mp3');
});

it('removes items that no longer exist in Dutch', function () {
    $nl = mixesPage('nl', 'muziek', [
        ['title' => 'Latin Vibes', 'audio' => '/storage/a.mp3'],
    ]);
    mixesPage('en', 'muziek', [
        ['title' => 'Latin Vibes', 'audio' => 'https://oud/a.mp3'],
        ['title' => 'Reggaeton Heat', 'audio' => 'https://oud/b.mp3'],
    ], $nl->id);

    $this->artisan('mixes:sync-media')->assertSuccessful();

    $items = Page::where('locale', 'en')->where('slug', 'muziek')->first()
        ->sections->firstWhere('section_type', 'mixes')->content['items'];

    expect($items)->toHaveCount(1);
    expect($items[0]['title'])->toBe('Latin Vibes');
});

it('changes nothing on a dry run', function () {
    $nl = mixesPage('nl', 'muziek', [['title' => 'A', 'audio' => '/storage/a.mp3']]);
    mixesPage('en', 'muziek', [['title' => 'A', 'audio' => 'https://oud/a.mp3']], $nl->id);

    $this->artisan('mixes:sync-media', ['--dry-run' => true])->assertSuccessful();

    $items = Page::where('locale', 'en')->where('slug', 'muziek')->first()
        ->sections->firstWhere('section_type', 'mixes')->content['items'];

    expect($items[0]['audio'])->toBe('https://oud/a.mp3');
});
