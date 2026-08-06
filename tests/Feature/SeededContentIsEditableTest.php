<?php

use App\Filament\Resources\Pages\Pages\EditPage;
use App\Models\Page;
use Database\Seeders\HomepageSeeder;
use Livewire\Livewire;

/**
 * Geseede content moet door het eigen bewerkformulier komen. Ging dit fout, dan
 * blokkeerde een te lange meta-omschrijving op de SEO-tab het opslaan van de
 * hele pagina — inclusief secties waar de beheerder wél aan gewerkt had, op een
 * tab waar de fout niet eens zichtbaar was.
 *
 * De grenzen komen uit PageResource\Schemas\PageForm (maxLength).
 */
const META_TITLE_MAX = 60;
const META_DESCRIPTION_MAX = 160;

it('seeds meta descriptions that fit the form limit', function () {
    $this->seed(HomepageSeeder::class);

    $tooLong = Page::all()
        ->filter(fn (Page $p) => mb_strlen((string) $p->meta_description) > META_DESCRIPTION_MAX)
        ->map(fn (Page $p) => "{$p->locale}/{$p->slug} (".mb_strlen((string) $p->meta_description).')')
        ->values()
        ->all();

    expect($tooLong)->toBe([]);
});

it('seeds meta titles that fit the form limit', function () {
    $this->seed(HomepageSeeder::class);

    $tooLong = Page::all()
        ->filter(fn (Page $p) => mb_strlen((string) $p->meta_title) > META_TITLE_MAX)
        ->map(fn (Page $p) => "{$p->locale}/{$p->slug} (".mb_strlen((string) $p->meta_title).')')
        ->values()
        ->all();

    expect($tooLong)->toBe([]);
});

/**
 * De kerntest: open een geseede pagina in de admin en sla haar ongewijzigd op.
 * Slaagt dit niet, dan kan de beheerder niets bewaren — ook niet het werk dat hij
 * op een ándere tab deed, want Filament valideert het hele formulier in één keer.
 */
it('saves every seeded page from the admin without validation errors', function (string $locale, string $slug) {
    $this->seed(HomepageSeeder::class);
    $this->actingAs(admin());

    $page = Page::where('locale', $locale)->where('slug', $slug)->firstOrFail();

    Livewire::test(EditPage::class, ['record' => $page->getRouteKey()])
        ->call('save')
        ->assertHasNoFormErrors();
})->with([
    // Home en muziek dragen de mixes-secties met geseede audio-URL's.
    ['nl', 'home'],
    ['nl', 'muziek'],
    ['en', 'home'],
    ['es', 'muziek'],
    ['nl', 'boeken'],
    ['nl', 'contact'],
]);

it('keeps the translated meta descriptions within the limit too', function () {
    $this->seed(HomepageSeeder::class);

    // De vertaalmap is op de NL-brontekst gesleuteld. Wordt de NL-tekst ingekort
    // zonder de sleutel mee te wijzigen, dan valt de vertaling stil terug op het
    // Nederlands — dit vangt dat op.
    $homepages = Page::where('is_homepage', true)->get()->keyBy('locale');

    expect($homepages)->toHaveCount(3);
    expect($homepages['en']->meta_description)->not->toBe($homepages['nl']->meta_description);
    expect($homepages['es']->meta_description)->not->toBe($homepages['nl']->meta_description);
});
