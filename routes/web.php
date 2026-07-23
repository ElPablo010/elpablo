<?php

use App\Http\Controllers\PublicPageController;
use App\Http\Controllers\SeoController;
use Illuminate\Support\Facades\Route;

// Filament is het enige login-systeem; de korte /login redirect ernaartoe.
Route::redirect('/login', '/admin/login')->name('login');

// SEO/GEO-assets — dynamisch zodat ze de live database + omgeving weerspiegelen.
// Vóór de catch-all geregistreerd.
Route::get('/sitemap.xml', [SeoController::class, 'sitemap'])->name('sitemap');
Route::get('/robots.txt', [SeoController::class, 'robots'])->name('robots');
Route::get('/llms.txt', [SeoController::class, 'llms'])->name('llms');

// Design-previews voor pagina's die nog niet via de Filament-builder bestaan.
// Bereikbaar voor ingelogde users als referentie naast de live versie.
// Conventie: resources/views/pages/previews/{slug}.blade.php
Route::middleware('auth')
    ->get('/design/{slug}', function (string $slug) {
        $view = "pages.previews.{$slug}";
        abort_unless(view()->exists($view), 404);

        return response()->view($view);
    })
    ->where('slug', '[a-z0-9-]+')
    ->name('design.preview');

// Meertalig: EN/ES draaien onder een locale-prefix. Vóór de NL catch-all
// geregistreerd; de whereIn beperkt {locale} tot exact 'en'/'es' zodat gewone
// NL-slugs (bv. /events) hier niet per ongeluk in vallen.
Route::prefix('{locale}')
    ->whereIn('locale', ['en', 'es'])
    ->group(function (): void {
        Route::get('/', [PublicPageController::class, 'show'])->name('page.home.localized');
        Route::get('/{slug}', [PublicPageController::class, 'show'])
            ->where('slug', '[a-z0-9-]+')
            ->name('page.show.localized');
    });

// Catch-all paginarouter (NL homepage + alle slugs). Sluit admin/livewire/storage uit.
Route::get('/{slug?}', [PublicPageController::class, 'show'])
    ->where('slug', '^(?!admin|login|livewire|storage|_debugbar|design).*$')
    ->name('page.show');
