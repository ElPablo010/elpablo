<?php

use App\Models\Page;
use App\Models\SeoActionItem;
use App\Services\SeoActionApplier;

/**
 * De SEO-laag schrijft content rechtstreeks in de page-builder. Deze tests
 * bewaken dat contract: welke sectietypes eruit komen, in welke taal, en dat de
 * gepubliceerde pagina daarna gewoon rendert.
 */
it('renders the SEO admin pages', function (string $path) {
    $this->actingAs(admin())
        ->get($path)
        ->assertOk();
})->with([
    '/admin/seo-dashboard',
    '/admin/seo-actions',
    '/admin/seo-keywords',
    '/admin/seo-settings',
    '/admin/general-settings',
]);

it('publishes a create_page action as a Dutch landing page with one H1', function () {
    $item = SeoActionItem::create([
        'action_type' => 'create_page',
        'priority' => 'high',
        'title' => 'Landingspagina dj huren',
        'problem' => 'Geen pagina voor dit keyword.',
        'fingerprint' => sha1('create_page|dj-huren'),
        'proposed' => [
            'slug' => 'dj-huren',
            'meta_title' => 'DJ huren',
            'meta_description' => 'Urban Latin DJ voor je feest.',
            'sections' => [
                ['section_type' => 'hero', 'content' => ['heading' => 'DJ huren voor je feest']],
                ['section_type' => 'text', 'content' => ['heading' => 'Waarom', 'body' => '<p>Twintig jaar ervaring.</p>']],
                ['section_type' => 'faq', 'content' => ['heading' => 'Veelgestelde vragen', 'items' => [
                    ['question' => 'Wat kost een DJ?', 'answer' => 'Vanaf een vast avondtarief.'],
                ]]],
                ['section_type' => 'cta', 'content' => ['heading' => 'Klaar om te boeken?', 'ctas' => [
                    ['label' => 'Boek nu', 'variant' => 'primary', 'link_type' => 'url', 'href' => '/boeken'],
                ]]],
            ],
        ],
    ]);

    app(SeoActionApplier::class)->apply($item);
    $item->refresh();

    $page = Page::find($item->created_page_id);

    expect($item->status)->toBe('published')
        ->and($page->locale)->toBe('nl')
        ->and($page->published)->toBeTrue()
        ->and($page->sections->pluck('section_type')->all())->toBe(['hero', 'text', 'faq', 'cta']);

    $html = $this->get('/dj-huren')->assertOk()->getContent();

    // De hero levert de H1; het tekstblok eronder mag er geen tweede toevoegen.
    expect(substr_count($html, '<h1'))->toBe(1);
});

it('merges an add_section FAQ into an existing FAQ block instead of duplicating it', function () {
    $page = Page::create([
        'title' => 'Boeken',
        'slug' => 'boeken',
        'locale' => 'nl',
        'published' => true,
    ]);
    $page->sections()->create([
        'section_type' => 'faq',
        'position' => 0,
        'locale' => 'nl',
        'content' => ['heading' => 'Veelgestelde vragen', 'items' => [
            ['question' => 'Wat kost een DJ?', 'answer' => 'Bestaand antwoord.'],
        ]],
    ]);

    $item = SeoActionItem::create([
        'action_type' => 'add_section',
        'priority' => 'medium',
        'title' => 'FAQ uitbreiden',
        'problem' => 'Vragen blijven onbeantwoord.',
        'fingerprint' => sha1('add_section|page-'.$page->id),
        'page_id' => $page->id,
        'proposed' => [
            'section_type' => 'faq',
            'content' => ['heading' => 'Veelgestelde vragen', 'items' => [
                // Duplicaat (andere interpunctie/hoofdletters) — mag niet opnieuw landen.
                ['question' => 'wat kost een dj', 'answer' => 'Ander antwoord.'],
                ['question' => 'Kom je ook buiten Antwerpen?', 'answer' => 'Ja, in heel Vlaanderen.'],
            ]],
        ],
    ]);

    app(SeoActionApplier::class)->apply($item);

    $faqs = $page->sections()->where('section_type', 'faq')->get();

    expect($faqs)->toHaveCount(1)
        ->and($faqs->first()->content['items'])->toHaveCount(2)
        ->and($faqs->first()->content['items'][0]['answer'])->toBe('Bestaand antwoord.');
});

it('keeps slugs unique per locale only', function () {
    Page::create(['title' => 'Book', 'slug' => 'dj-huren', 'locale' => 'en', 'published' => true]);

    $item = SeoActionItem::create([
        'action_type' => 'create_page',
        'priority' => 'low',
        'title' => 'DJ huren',
        'problem' => 'Test.',
        'fingerprint' => sha1('create_page|dj-huren-locale'),
        'proposed' => [
            'slug' => 'dj-huren',
            'sections' => [['section_type' => 'hero', 'content' => ['heading' => 'DJ huren']]],
        ],
    ]);

    app(SeoActionApplier::class)->apply($item);

    // De EN-pagina bezet dezelfde slug, maar in een andere taal — de NL-pagina
    // mag daardoor geen "-2" achter zich krijgen.
    expect(Page::find($item->refresh()->created_page_id)->slug)->toBe('dj-huren');
});
