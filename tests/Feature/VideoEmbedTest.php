<?php

use App\Models\Page;
use App\Support\VideoEmbed;

/**
 * Beheerders plakken YouTube/Vimeo-pagina-URL's in het video-veld van de
 * text-media-sectie; die spelen niet in een kaal <video>-element (lege speler
 * op 0:00). VideoEmbed vertaalt ze naar een iframe-embed-URL; directe
 * bestanden blijven de native speler gebruiken.
 */
it('converts youtube and vimeo page urls to embed urls', function (string $input, ?string $expected) {
    expect(VideoEmbed::url($input))->toBe($expected);
})->with([
    ['https://www.youtube.com/watch?v=CSb6sirVb1M', 'https://www.youtube-nocookie.com/embed/CSb6sirVb1M'],
    ['https://youtu.be/CSb6sirVb1M?si=W88uVfwnIbRwVWFu', 'https://www.youtube-nocookie.com/embed/CSb6sirVb1M'],
    ['https://www.youtube.com/shorts/CSb6sirVb1M', 'https://www.youtube-nocookie.com/embed/CSb6sirVb1M'],
    ['https://www.youtube.com/embed/CSb6sirVb1M', 'https://www.youtube-nocookie.com/embed/CSb6sirVb1M'],
    ['https://vimeo.com/76979871', 'https://player.vimeo.com/video/76979871'],
    ['https://player.vimeo.com/video/76979871', 'https://player.vimeo.com/video/76979871'],
    // Directe bestanden en al de rest: geen embed, native <video>.
    ['https://www.el-pablo.com/storage/website-video/set.mp4', null],
    ['/storage/website-video/set.mp4', null],
]);

function textMediaVideoPage(string $videoUrl): void
{
    $page = Page::create([
        'title' => 'Home',
        'slug' => 'home',
        'locale' => 'nl',
        'is_homepage' => true,
        'published' => true,
    ]);

    $page->sections()->create([
        'section_type' => 'text_media',
        'position' => 0,
        'locale' => 'nl',
        'content' => [
            'heading' => 'Wie is El Pablo',
            'media_type' => 'video',
            'video_url' => $videoUrl,
        ],
    ]);
}

it('renders a youtube url as an iframe embed', function () {
    textMediaVideoPage('https://youtu.be/CSb6sirVb1M?si=W88uVfwnIbRwVWFu');

    $response = $this->get('/')->assertOk();

    $response->assertSee('https://www.youtube-nocookie.com/embed/CSb6sirVb1M', false);
    $response->assertDontSee('<video', false);
});

it('renders a direct mp4 url with the native video player', function () {
    textMediaVideoPage('/storage/website-video/set.mp4');

    $response = $this->get('/')->assertOk();

    $response->assertSee('<video src="/storage/website-video/set.mp4"', false);
    $response->assertDontSee('youtube-nocookie', false);
});
