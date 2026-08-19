<?php

use App\Models\Page;

/**
 * AudioPickerField bewaart een kant-en-klare relatieve URL
 * ('/storage/website-audio/…'). De mixes-sectie prefixte élk niet-http-pad
 * nogmaals met /storage, waardoor spelers en downloads op live naar
 * '/storage/storage/…' wezen — allemaal 404. Alleen een kaal disk-pad mag de
 * prefix nog krijgen.
 */
function mixesAudioPage(array $items): Page
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
        'content' => ['heading' => 'Sets', 'items' => $items],
    ]);

    return $page;
}

it('renders picker urls, bare disk paths and absolute urls each exactly once', function () {
    mixesAudioPage([
        ['title' => 'Picker', 'audio' => '/storage/website-audio/picker.mp3'],
        ['title' => 'Kaal pad', 'audio' => 'website-audio/kaal.mp3'],
        ['title' => 'Absoluut', 'audio' => 'https://cdn.example.com/oud.mp3'],
    ]);

    $response = $this->get('/muziek')->assertOk();

    $response->assertSee('src="/storage/website-audio/picker.mp3"', false);
    $response->assertSee('src="/storage/website-audio/kaal.mp3"', false);
    $response->assertSee('src="https://cdn.example.com/oud.mp3"', false);
    $response->assertDontSee('/storage/storage/');
});
