<?php

use App\Http\Controllers\EventController;
use App\Http\Controllers\MixtapeController;
use App\Http\Controllers\PublicPageController;
use App\Http\Controllers\SeoController;
use App\Http\Controllers\TicketStatusController;
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

// Stripe-webhook (CSRF-vrij via bootstrap/app.php; de handtekening verifieert).
Route::post('/stripe/webhook', [App\Http\Controllers\StripeWebhookController::class, 'handle'])->name('stripe.webhook');

// Publieke ticketstatuspagina — de QR-code op elk ticket wijst hierheen.
Route::get('/t/{token}', [TicketStatusController::class, 'show'])->name('ticket.status');

// Meertalig: EN/ES draaien onder een locale-prefix. Vóór de NL catch-all
// geregistreerd; de whereIn beperkt {locale} tot exact 'en'/'es' zodat gewone
// NL-slugs (bv. /events) hier niet per ongeluk in vallen.
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

        Route::get('/', [PublicPageController::class, 'show'])->name('page.home.localized');
        Route::get('/{slug}', [PublicPageController::class, 'show'])
            ->where('slug', '[a-z0-9-]+')
            ->name('page.show.localized');
    });

// Events (NL, op de root) — vóór de catch-all geregistreerd.
Route::get('/events', [EventController::class, 'index'])->name('events.index');
Route::get('/events/{slug}', [EventController::class, 'show'])
    ->where('slug', '[a-z0-9-]+')
    ->name('events.show');
Route::get('/events/{slug}/bedankt', [EventController::class, 'thanks'])
    ->where('slug', '[a-z0-9-]+')
    ->name('events.thanks');

// Mixtape-detail (NL, op de root) — vóór de catch-all geregistreerd.
Route::get('/mixtapes/{slug}', [MixtapeController::class, 'show'])
    ->where('slug', '[a-z0-9-]+')
    ->name('mixtapes.show');

// Catch-all paginarouter (NL homepage + alle slugs). Sluit admin/livewire/storage uit.
// events/t/stripe staan er expliciet bij: de route-volgorde beschermt al, maar zo
// is de intentie leesbaar én blijft dat zo als routes ooit herschikt worden.
Route::get('/{slug?}', [PublicPageController::class, 'show'])
    // 'mixtapes/' mét slash: bare /mixtapes en /mixtapes_categorie/* (oude
    // WP-URL's) moeten hier blijven landen zodat de redirect-middleware ze
    // vangt — die draait pas als een route matcht.
    ->where('slug', '^(?!admin|login|livewire|storage|_debugbar|design|events|mixtapes/|t/|stripe).*$')
    ->name('page.show');
