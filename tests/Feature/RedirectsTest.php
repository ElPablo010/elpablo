<?php

use App\Models\Redirect;
use Database\Seeders\RedirectSeeder;
use Illuminate\Support\Facades\Cache;

/**
 * De oude WordPress-site (www.el-pablo.com) had 72 geïndexeerde URL's; de nieuwe
 * site heeft er 7 per taal. Deze tests bewaken dat de RedirectSeeder die kloof
 * dicht, en dat wat bewust géén redirect kreeg netjes op de eigen 404 landt.
 */
beforeEach(function () {
    // De middleware cachet de volledige redirect-map 300s; per test vers beginnen.
    Cache::forget(App\Http\Middleware\HandleRedirects::CACHE_KEY);
});

it('redirects old WordPress URLs to their new counterpart', function (string $from, string $to) {
    $this->seed(RedirectSeeder::class);

    $this->get($from)->assertRedirect($to);
})->with([
    // De belangrijkste: de oude boekings-/contactpagina.
    ['/contact-bookings', '/boeken'],
    // WordPress schreef trailing slashes; de middleware trimt ze.
    ['/contact-bookings/', '/boeken'],
    ['/urban-latin-dj', '/over'],
    ['/privacy-policy', '/privacybeleid'],
    // Multi-segment paden moeten de catch-all route halen, anders draait de
    // middleware (die in de web-group zit) nooit.
    ['/mixtapes', '/muziek'],
    ['/mixtapes/salchata-2021-vol-2', '/muziek'],
    ['/mixtapes_categorie/reggaeton', '/muziek'],
    ['/downloads/rumba-transition-up', '/muziek'],
    // Geen equivalent (nog): naar de homepage.
    ['/events/salsa-the-beach', '/'],
    ['/party_shots/ark-beach', '/'],
    ['/tag/urban', '/'],
]);

it('uses a permanent 301 so link equity carries over', function () {
    $this->seed(RedirectSeeder::class);

    expect(Redirect::pluck('status_code')->unique()->all())->toBe([301]);
});

it('leaves the old blog posts and WordPress internals to 404', function (string $path) {
    $this->seed(RedirectSeeder::class);

    expect(Redirect::where('from', $path)->exists())->toBeFalse();
})->with([
    '/news',
    '/hello-world',
    '/el-pablo-pic-1',
    '/marco-carola-music-on',
    '/ajax',
]);

it('renders the branded 404 page with noindex', function () {
    $this->get('/bestaat-niet')
        ->assertNotFound()
        ->assertSee('Deze track staat niet meer in de set')
        ->assertSee('noindex', escape: false);
});

it('renders the 404 page in the requested locale', function () {
    $this->get('/en/bestaat-niet')
        ->assertNotFound()
        ->assertSee('This track is no longer in the set');
});

it('is idempotent so it can be re-run safely on production', function () {
    $this->seed(RedirectSeeder::class);
    $first = Redirect::count();

    $this->seed(RedirectSeeder::class);

    expect(Redirect::count())->toBe($first);
});
