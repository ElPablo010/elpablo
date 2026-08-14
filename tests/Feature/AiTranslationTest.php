<?php

use App\Models\Event;
use App\Models\Page;
use App\Services\Translation\ClaudeTranslator;
use App\Services\Translation\EventTranslator;
use App\Services\Translation\PageTranslator;

/**
 * De AI-vertaallaag (geport uit ark-van-noe via de make-multilingual-skill).
 * De API-laag wordt vervangen: deze tests gaan over wat we in en uit de
 * vertaling stoppen, niet over het model aan de andere kant.
 */
function fakeClaudeTranslator(): object
{
    $fake = new class extends ClaudeTranslator
    {
        public array $received = [];

        public function translate(array $texts, string $fromLocale, string $toLocale, ?string $context = null): array
        {
            $this->received = $texts;

            return array_map(fn (string $text) => strtoupper($toLocale).': '.$text, $texts);
        }
    };

    test()->swap(ClaudeTranslator::class, $fake);

    return $fake;
}

it('vertaalt een pagina naar een gekoppelde rij met dezelfde slug', function () {
    $fake = fakeClaudeTranslator();

    $page = Page::create(['title' => 'Boeken', 'slug' => 'boeken', 'locale' => 'nl', 'published' => true]);
    $page->sections()->create([
        'section_type' => 'hero',
        'position' => 0,
        'content' => [
            'heading' => 'Boek El Pablo',
            'background' => 'primary',
            'cta' => ['label' => 'Vraag een offerte', 'href' => '/contact'],
        ],
    ]);

    $translation = app(PageTranslator::class)->translate($page->fresh('sections'), 'es');

    // Structuurvelden en links gaan niet mee naar het model ($skipKeys);
    // de href blijft NL-vormig en wordt bij het renderen gelokaliseerd.
    expect($fake->received)->toHaveKey('s0.heading')
        ->and($fake->received)->toHaveKey('s0.cta.label')
        ->and($fake->received)->not->toHaveKey('s0.background')
        ->and($fake->received)->not->toHaveKey('s0.cta.href');

    $content = $translation->sections->first()->content;

    expect($translation->locale)->toBe('es')
        ->and($translation->translation_of)->toBe($page->id)
        // Gedeelde slug per taal: Locale::href() lokaliseert met enkel de prefix.
        ->and($translation->slug)->toBe('boeken')
        ->and($content['heading'])->toBe('ES: Boek El Pablo')
        ->and($content['background'])->toBe('primary')
        ->and($content['cta']['href'])->toBe('/contact');
});

it('werkt een bestaande vertaling bij in plaats van een tweede aan te maken', function () {
    fakeClaudeTranslator();

    $page = Page::create(['title' => 'Muziek', 'slug' => 'muziek', 'locale' => 'nl', 'published' => true]);
    $existing = Page::create(['title' => 'Old music', 'slug' => 'muziek', 'locale' => 'en', 'translation_of' => $page->id]);

    $translation = app(PageTranslator::class)->translate($page, 'en');

    expect($translation->id)->toBe($existing->id)
        ->and($translation->title)->toBe('EN: Muziek')
        ->and(Page::where('locale', 'en')->where('slug', 'muziek')->count())->toBe(1);
});

it('vertaalt een event naar zijn event_translations-rij', function () {
    fakeClaudeTranslator();

    $event = Event::create([
        'slug' => 'zomerfeest',
        'name' => 'Zomerfeest',
        'short_description' => 'Latin vibes aan het water.',
        'description' => '<p>Een hele avond urban latin.</p>',
        'start_date' => '2026-09-01',
        'published' => true,
    ]);

    app(App\Services\Translation\TranslateRecord::class)->handle($event, 'es');

    $translation = $event->translations()->where('locale', 'es')->first();

    expect($translation)->not->toBeNull()
        ->and($translation->name)->toBe('ES: Zomerfeest')
        ->and($translation->short_description)->toBe('ES: Latin vibes aan het water.')
        ->and($translation->hasContent())->toBeTrue();

    // Nogmaals vertalen overschrijft dezelfde rij (geen duplicaten).
    app(EventTranslator::class)->translate($event, 'es');

    expect($event->translations()->where('locale', 'es')->count())->toBe(1);
});
