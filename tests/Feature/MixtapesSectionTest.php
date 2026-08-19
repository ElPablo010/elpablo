<?php

use App\Models\Mixtape;
use App\Models\Page;

/**
 * Mixtapes zijn een eigen posttype; de mixes-sectie toont ofwel alle
 * gepubliceerde mixtapes (show_all, in de versleepbare admin-volgorde) ofwel
 * een handmatige selectie (mixtape_ids, in selectievolgorde).
 *
 * De audio-URL-resolutie verhuisde mee naar Mixtape::resolvedAudioUrl():
 * AudioPickerField bewaart een kant-en-klare relatieve URL
 * ('/storage/website-audio/…'); alleen een kaal disk-pad mag nog de
 * /storage-prefix krijgen, anders wijzen spelers en downloads naar
 * '/storage/storage/…' — allemaal 404.
 */
function mixtapesPage(array $sectionContent): Page
{
    $page = Page::create([
        'title' => 'Muziek',
        'slug' => 'muziek',
        'locale' => 'nl',
        'published' => true,
    ]);

    $page->sections()->create([
        'section_type' => 'mixes',
        'position' => 0,
        'locale' => 'nl',
        'content' => ['heading' => 'Sets', ...$sectionContent],
    ]);

    return $page;
}

it('renders picker urls, bare disk paths and absolute urls each exactly once', function () {
    Mixtape::create(['title' => 'Picker', 'audio_url' => '/storage/website-audio/picker.mp3']);
    Mixtape::create(['title' => 'Kaal pad', 'audio_url' => 'website-audio/kaal.mp3']);
    Mixtape::create(['title' => 'Absoluut', 'audio_url' => 'https://cdn.example.com/oud.mp3']);

    mixtapesPage(['show_all' => true]);

    $response = $this->get('/muziek')->assertOk();

    $response->assertSee('src="/storage/website-audio/picker.mp3"', false);
    $response->assertSee('src="/storage/website-audio/kaal.mp3"', false);
    $response->assertSee('src="https://cdn.example.com/oud.mp3"', false);
    $response->assertDontSee('/storage/storage/');
});

it('shows all published mixtapes in admin order when show_all is on', function () {
    Mixtape::create(['title' => 'Tweede', 'audio_url' => '/storage/b.mp3', 'position' => 2]);
    Mixtape::create(['title' => 'Eerste', 'audio_url' => '/storage/a.mp3', 'position' => 1]);
    Mixtape::create(['title' => 'Verborgen', 'audio_url' => '/storage/c.mp3', 'position' => 0, 'published' => false]);

    mixtapesPage(['show_all' => true]);

    $response = $this->get('/muziek')->assertOk();

    $response->assertSeeInOrder(['Eerste', 'Tweede']);
    $response->assertDontSee('Verborgen');
});

it('shows only the selected mixtapes in selection order when show_all is off', function () {
    $a = Mixtape::create(['title' => 'Alpha', 'audio_url' => '/storage/a.mp3', 'position' => 0]);
    $b = Mixtape::create(['title' => 'Bravo', 'audio_url' => '/storage/b.mp3', 'position' => 1]);
    Mixtape::create(['title' => 'Charlie', 'audio_url' => '/storage/c.mp3', 'position' => 2]);

    // Selectievolgorde wijkt bewust af van de positie-volgorde.
    mixtapesPage(['show_all' => false, 'mixtape_ids' => [$b->id, $a->id]]);

    $response = $this->get('/muziek')->assertOk();

    $response->assertSeeInOrder(['Bravo', 'Alpha']);
    $response->assertDontSee('Charlie');
});

it('hides the download button when a mixtape disallows downloads', function () {
    Mixtape::create(['title' => 'Geen download', 'audio_url' => '/storage/a.mp3', 'allow_download' => false]);

    mixtapesPage(['show_all' => true]);

    $this->get('/muziek')->assertOk()->assertDontSee('Download');
});
