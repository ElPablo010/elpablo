<?php

use App\Models\Page;
use App\Models\WebsiteMedia;
use Illuminate\Support\Facades\Storage;

/**
 * Media-URL's worden als string opgeslagen (in page_sections.content en
 * website_media). Staat het domein daarin gebakken, dan geeft élke afbeelding een
 * 404 zodra de site naar een ander domein verhuist — en dat merk je pas ná de
 * cutover. Vandaar: de public-disk levert relatieve URL's.
 */
it('builds public storage urls relatively', function () {
    expect(config('filesystems.disks.public.url'))->toBe('/storage');
    expect(Storage::disk('public')->url('website-media/foo.webp'))->toBe('/storage/website-media/foo.webp');
});

it('is unaffected by the configured app url', function () {
    config()->set('app.url', 'https://el-pablo.com');

    expect(Storage::disk('public')->url('website-media/foo.webp'))
        ->toBe('/storage/website-media/foo.webp');
});

it('stores no domain-bound storage urls in the database', function () {
    $pattern = '#https?://[^/\s"\']+/storage/#i';

    $offenders = [];

    foreach (WebsiteMedia::all() as $media) {
        if (preg_match($pattern, (string) $media->url.' '.(string) $media->fallback_url)) {
            $offenders[] = "website_media#{$media->id}";
        }
    }

    foreach (Page::with('sections')->get() as $page) {
        if (preg_match($pattern, (string) $page->seo_image_url)) {
            $offenders[] = "pages#{$page->id}";
        }

        foreach ($page->sections as $section) {
            if (preg_match($pattern, json_encode($section->content, JSON_UNESCAPED_SLASHES))) {
                $offenders[] = "page_sections#{$section->id}";
            }
        }
    }

    expect($offenders)->toBe([]);
});

it('still renders og:image as an absolute url', function () {
    // Sociale media accepteren geen relatieve og:image; Seo::absolute() plakt
    // APP_URL ervoor. Dat moet blijven werken nu de bron relatief is.
    $page = Page::create([
        'title' => 'Test',
        'slug' => 'home',
        'is_homepage' => true,
        'published' => true,
        'seo_image_url' => '/storage/website-media/foo.webp',
    ]);
    $page->sections()->create([
        'section_type' => 'hero',
        'position' => 0,
        'content' => ['heading' => 'Titel'],
    ]);

    $this->get('/')
        ->assertOk()
        ->assertSee(rtrim(config('app.url'), '/').'/storage/website-media/foo.webp', escape: false);
});
