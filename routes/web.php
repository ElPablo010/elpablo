<?php

use App\Http\Controllers\EventController;
use App\Http\Controllers\MixtapeController;
use App\Http\Controllers\StripeWebhookController;
use App\Http\Controllers\TicketStatusController;
use Illuminate\Support\Facades\Route;

// Filament is het enige login-systeem; de korte /login redirect ernaartoe.
Route::redirect('/login', '/admin/login')->name('login');

// sitemap.xml, robots.txt, llms.txt, de pagina's (NL op de root, EN/ES onder
// /en en /es) en de catch-all komen uit webgoeroe/core, ná deze routes. Events
// en mixtapes in de sitemap/llms.txt: App\Support\ContentSeo.

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

// Stripe-webhook (CSRF-vrij via bootstrap/app.php; de handtekening verifieert).
Route::post('/stripe/webhook', [StripeWebhookController::class, 'handle'])->name('stripe.webhook');

// Publieke ticketstatuspagina — de QR-code op elk ticket wijst hierheen.
Route::get('/t/{token}', [TicketStatusController::class, 'show'])->name('ticket.status');

// Meertalig: EN/ES draaien onder een locale-prefix. Vóór de pagina-routes van
// de core geregistreerd; de whereIn beperkt {locale} tot exact 'en'/'es' zodat
// gewone NL-slugs (bv. /events) hier niet per ongeluk in vallen.
Route::prefix('{locale}')
    ->whereIn('locale', ['en', 'es'])
    ->group(function (): void {
        // Events vóór de {slug}-route, anders zou /en/events als pagina-slug matchen.
        Route::get('/events', [EventController::class, 'index'])->name('events.index.localized');
        Route::get('/events/{slug}', [EventController::class, 'show'])
            ->where('slug', '[a-z0-9-]+')
            ->name('events.show.localized');
        Route::get('/events/{slug}/bedankt', [EventController::class, 'thanks'])
            ->where('slug', '[a-z0-9-]+')
            ->name('events.thanks.localized');

        // Mixtape-detail (deelbare link) — ook vóór de {slug}-route.
        Route::get('/mixtapes/{slug}', [MixtapeController::class, 'show'])
            ->where('slug', '[a-z0-9-]+')
            ->name('mixtapes.show.localized');
    });

// Google OAuth (Search Console + Analytics) komt uit de package
// webgoeroe/seo-growth (admin/search-console/oauth/*), vóór deze routes.

// Events (NL, op de root) — vóór de catch-all van de core.
Route::get('/events', [EventController::class, 'index'])->name('events.index');
Route::get('/events/{slug}', [EventController::class, 'show'])
    ->where('slug', '[a-z0-9-]+')
    ->name('events.show');
Route::get('/events/{slug}/bedankt', [EventController::class, 'thanks'])
    ->where('slug', '[a-z0-9-]+')
    ->name('events.thanks');

// Mixtape-detail (NL, op de root) — vóór de catch-all van de core.
Route::get('/mixtapes/{slug}', [MixtapeController::class, 'show'])
    ->where('slug', '[a-z0-9-]+')
    ->name('mixtapes.show');

// De catch-all paginarouter (NL homepage + alle slugs) registreert de core als
// allerlaatste route. Uitgesloten paden (events, mixtapes/, t/, stripe, …):
// config/core.php → routes.exclude.
