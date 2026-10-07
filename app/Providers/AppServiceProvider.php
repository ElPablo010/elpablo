<?php

namespace App\Providers;

use App\Contracts\PaymentGateway;
use App\Filament\Actions\TranslateAction;
use App\Filament\Schemas\Sections\EventsFields;
use App\Filament\Schemas\Sections\MixesFields;
use App\Services\StripeGateway;
use App\Services\Translation\TranslationMediaSync;
use App\Support\ContentSeo;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Webgoeroe\Core\Core;
use Webgoeroe\Core\Events\PageSectionsSaved;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Betaalprovider achter een interface, zodat tests een fake binden.
        $this->app->bind(PaymentGateway::class, StripeGateway::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->registerBlocks();
        $this->registerTranslation();

        // Events en mixtapes in sitemap.xml, llms.txt en de meta/JSON-LD
        // (Seo::fromEvent, Seo::fromEventIndex, Seo::fromMixtape).
        ContentSeo::register();
    }

    /**
     * Blokken die enkel deze site heeft. De publieke views staan in
     * resources/views/components/site/sections.
     */
    protected function registerBlocks(): void
    {
        Core::blocks()
            ->register('events', 'Events', EventsFields::class)
            ->register('mixes', 'Mixes / muziek', MixesFields::class);
    }

    /**
     * De vertaallaag van deze site (Claude, make-multilingual) op de schermen
     * van de core: "Vertalen met AI" op de paginatabel, en media die na het
     * bewaren van een NL-pagina naar haar EN/ES-vertalingen gaan.
     */
    protected function registerTranslation(): void
    {
        Core::pageTable()
            ->recordActions(fn (): array => [TranslateAction::record(subject: 'pagina')])
            ->bulkActions(fn (): array => [TranslateAction::bulk(subjectPlural: 'pagina\'s')]);

        // Foto's en video's zijn taalloos: een bewerkte NL-pagina zet ze door
        // naar haar EN/ES-vertalingen (tekst blijft ongemoeid).
        Event::listen(PageSectionsSaved::class, function (PageSectionsSaved $event): void {
            app(TranslationMediaSync::class)->sync($event->page->refresh());
        });
    }
}
