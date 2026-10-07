<?php

namespace App\Support;

use App\Models\Event;
use App\Models\Mixtape;
use App\Models\WebsiteMedia;
use Illuminate\Support\Str;
use Webgoeroe\Core\Core;
use Webgoeroe\Core\Support\Locale;
use Webgoeroe\Core\Support\Seo;
use Webgoeroe\Core\Support\SiteFooter;

/**
 * SEO van de eigen content van deze site (events en mixtapes), bovenop de
 * site-basis van webgoeroe/core:
 *
 *   - meta-bundels + JSON-LD als Seo-macro's: Seo::fromEvent($event, $locale),
 *     Seo::fromEventIndex($locale), Seo::fromMixtape($mixtape, $locale) en
 *     Seo::eventAlternates($event);
 *   - /sitemap.xml: het eventoverzicht, elk gepubliceerd event en elke mixtape,
 *     met hreflang-alternates per taal;
 *   - /llms.txt: de sectie "Events" (aankomende events).
 *
 * Geregistreerd in AppServiceProvider::boot(). Pagina's, hreflang, de
 * LocalBusiness-node en Seo::brandName() komen uit de core.
 */
class ContentSeo
{
    public static function register(): void
    {
        // Callables i.p.v. closures met self::: Macroable bindt een closure aan
        // de Seo-klasse, waardoor self:: daar naar Seo zelf zou wijzen.
        Seo::macro('fromEvent', [self::class, 'fromEvent']);
        Seo::macro('fromEventIndex', [self::class, 'fromEventIndex']);
        Seo::macro('fromMixtape', [self::class, 'fromMixtape']);
        Seo::macro('eventAlternates', [self::class, 'eventAlternates']);

        Core::seo()
            ->sitemapSource(fn (): array => ContentSeo::sitemapEntries())
            ->llmsSection(fn (): array => ContentSeo::llmsLines());
    }

    /**
     * Meta-bundel + JSON-LD voor een event-detailpagina, in een specifieke taal.
     *
     * @return array<string, mixed>
     */
    public static function fromEvent(Event $event, string $locale): array
    {
        $canonical = Seo::absoluteUrl($event->localizedPath($locale));

        $title = filled($event->meta_title) ? $event->meta_title : $event->translated('name', $locale);
        $description = filled($event->meta_description)
            ? $event->meta_description
            : Str::of((string) $event->translated('short_description', $locale))->stripTags()->squish()->value();

        $dimensions = filled($event->image_url) ? WebsiteMedia::dimensionsForUrl($event->image_url) : [];

        return [
            'title' => $title,
            'description' => $description,
            'canonical' => $canonical,
            'robots' => 'index, follow',
            'image' => $event->image_url,
            'imageAlt' => filled($event->image_alt) ? $event->image_alt : $title,
            'imageWidth' => $dimensions['width'] ?? null,
            'imageHeight' => $dimensions['height'] ?? null,
            'type' => 'website',
            'locale' => $locale,
            'alternates' => self::eventAlternates($event),
            'schema' => [self::eventNode($event, $locale, $canonical, $description)],
        ];
    }

    /**
     * Meta-bundel voor het eventoverzicht (/events) in een specifieke taal.
     *
     * @return array<string, mixed>
     */
    public static function fromEventIndex(string $locale): array
    {
        $canonical = Seo::absoluteUrl(Locale::href('/events', $locale));

        $alternates = [];
        foreach (Locale::supported() as $alt) {
            $alternates[$alt] = Seo::absoluteUrl(Locale::href('/events', $alt));
        }

        $title = __('Events');
        $description = __('Alle events van :name: data, locaties en tickets.', ['name' => Seo::siteName()]);

        return [
            'title' => $title,
            'description' => $description,
            'canonical' => $canonical,
            'robots' => 'index, follow',
            'image' => null,
            'imageAlt' => null,
            'imageWidth' => null,
            'imageHeight' => null,
            'type' => 'website',
            'locale' => $locale,
            'alternates' => $alternates,
            'schema' => [[
                '@type' => 'CollectionPage',
                '@id' => $canonical.'#collection',
                'url' => $canonical,
                'name' => $title,
                'isPartOf' => ['@id' => Seo::baseUrl().'/#website'],
                'inLanguage' => Seo::htmlLang($locale),
            ]],
        ];
    }

    /**
     * Meta-bundel + JSON-LD voor een mixtape-detailpagina. Mixtapes zijn
     * taal-onafhankelijk: elke taal toont dezelfde inhoud, dus de alternates
     * dekken alle talen.
     *
     * @return array<string, mixed>
     */
    public static function fromMixtape(Mixtape $mixtape, string $locale): array
    {
        $canonical = Seo::absoluteUrl($mixtape->localizedPath($locale));

        $description = filled($mixtape->subtitle)
            ? $mixtape->subtitle
            : __('Beluister de mixtape :title van :name.', ['title' => $mixtape->title, 'name' => Seo::siteName()]);

        $dimensions = filled($mixtape->cover_url) ? WebsiteMedia::dimensionsForUrl($mixtape->cover_url) : [];

        $alternates = [];
        foreach (Locale::supported() as $alt) {
            $alternates[$alt] = Seo::absoluteUrl($mixtape->localizedPath($alt));
        }

        $schema = [
            '@type' => 'MusicPlaylist',
            '@id' => $canonical.'#mixtape',
            'url' => $canonical,
            'name' => $mixtape->title,
            'description' => $description,
            'isPartOf' => ['@id' => Seo::baseUrl().'/#website'],
        ];

        if (filled($mixtape->cover_url)) {
            $schema['image'] = Seo::absoluteUrl($mixtape->cover_url);
        }

        if ($audio = $mixtape->resolvedAudioUrl()) {
            $schema['audio'] = [
                '@type' => 'AudioObject',
                'contentUrl' => Seo::absoluteUrl($audio),
                'encodingFormat' => 'audio/mpeg',
            ];
        }

        return [
            'title' => $mixtape->title,
            'description' => $description,
            'canonical' => $canonical,
            'robots' => 'index, follow',
            'image' => $mixtape->cover_url,
            'imageAlt' => $mixtape->title,
            'imageWidth' => $dimensions['width'] ?? null,
            'imageHeight' => $dimensions['height'] ?? null,
            'type' => 'website',
            'locale' => $locale,
            'alternates' => $alternates,
            'schema' => [$schema],
        ];
    }

    /**
     * hreflang-alternates voor een event: NL altijd (de bron), EN/ES enkel als
     * er een vertaling mét inhoud bestaat — een lege placeholder-rij zou anders
     * naar een pagina wijzen die gewoon Nederlands toont.
     *
     * @return array<string, string> locale => absolute URL
     */
    public static function eventAlternates(Event $event): array
    {
        $result = [Locale::defaultLocale() => Seo::absoluteUrl($event->localizedPath(Locale::defaultLocale()))];

        foreach (Locale::supported() as $locale) {
            if ($locale === Locale::defaultLocale()) {
                continue;
            }

            if ($event->translationFor($locale)?->hasContent()) {
                $result[$locale] = Seo::absoluteUrl($event->localizedPath($locale));
            }
        }

        return $result;
    }

    /**
     * schema.org Event-node met een Offer per tickettype (actuele promoprijs,
     * beschikbaarheid en verkoopdeadline). Sterk voor event-rich-results.
     *
     * @return array<string, mixed>
     */
    private static function eventNode(Event $event, string $locale, string $canonical, string $description): array
    {
        // Een uur in de admin is een uur aan de deur: Brusselse tijd. De app
        // draait op UTC, dus shiftTimezone() hangt diezelfde wijzerplaat aan de
        // juiste zone — zonder shift zou 22:00 als "+00:00" naar Google gaan en
        // die er middernacht van maken.
        $startDate = $event->start_date->format('Y-m-d');
        if ($event->start_time) {
            $startDate = $event->start_date
                ->copy()
                ->setTimeFromTimeString($event->start_time)
                ->shiftTimezone(Seo::TIMEZONE)
                ->format('Y-m-d\TH:i:sP');
        }

        $endBase = $event->end_date ?? $event->start_date;
        $endDate = $endBase->format('Y-m-d');
        if ($event->end_time) {
            $end = $endBase->copy()->setTimeFromTimeString($event->end_time);
            // Een einduur vóór het startuur op dezelfde dag = na middernacht.
            if (! $event->end_date && $event->start_time && $event->end_time < $event->start_time) {
                $end = $end->addDay();
            }
            $endDate = $end->shiftTimezone(Seo::TIMEZONE)->format('Y-m-d\TH:i:sP');
        }

        $offers = [];
        foreach ($event->eventTicketTypes as $pivot) {
            $price = $event->currentPriceFor($pivot->ticket_type_id);
            $offers[] = array_filter([
                '@type' => 'Offer',
                'name' => $pivot->ticketType?->nameFor($locale),
                'price' => number_format($price['current'], 2, '.', ''),
                'priceCurrency' => 'EUR',
                'availability' => $pivot->isSoldOut() || ! $pivot->salesOpen()
                    ? 'https://schema.org/SoldOut'
                    : 'https://schema.org/InStock',
                // Google wil weten vanaf wanneer het ticket te koop is. Zonder
                // voorverkoopdatum is dat het moment dat het tickettype
                // aangemaakt werd: vanaf dan stond het online.
                'validFrom' => $pivot->sales_start_date
                    ?: $pivot->created_at?->format('Y-m-d'),
                'validThrough' => $pivot->sales_end_date,
                'url' => $canonical,
            ], fn ($v) => filled($v));
        }

        return array_filter([
            '@type' => 'Event',
            '@id' => $canonical.'#event',
            'url' => $canonical,
            'name' => $event->translated('name', $locale),
            'description' => $description,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'eventStatus' => $event->isCancelled()
                ? 'https://schema.org/EventCancelled'
                : 'https://schema.org/EventScheduled',
            'eventAttendanceMode' => 'https://schema.org/OfflineEventAttendanceMode',
            'location' => array_filter([
                '@type' => 'Place',
                'name' => $event->venue_name,
                'address' => array_filter([
                    '@type' => 'PostalAddress',
                    'streetAddress' => $event->venue_address,
                    'postalCode' => $event->venue_postal_code,
                    'addressLocality' => $event->venue_city,
                    'addressCountry' => 'BE',
                ], fn ($v) => filled($v)),
            ], fn ($v) => filled($v)),
            'image' => Seo::absoluteUrl($event->image_url),
            'performer' => self::performerNodes($event),
            'organizer' => ['@id' => Seo::baseUrl().'/#business'],
            'offers' => $offers !== [] ? $offers : null,
            'inLanguage' => Seo::htmlLang($locale),
        ], fn ($v) => filled($v));
    }

    /**
     * De artiest(en) van een event als schema.org-nodes. De DJ zelf krijgt een
     * vast @id plus zijn socials, zodat Google al zijn optredens aan één
     * entiteit kan koppelen; gastartiesten uit de line-up blijven een naam.
     *
     * @return array<int, array<string, mixed>>
     */
    private static function performerNodes(Event $event): array
    {
        $brand = Seo::brandName();

        return array_map(function (string $name) use ($brand): array {
            if ($name !== $brand) {
                return ['@type' => 'Person', 'name' => $name];
            }

            return array_filter([
                '@type' => 'Person',
                '@id' => Seo::baseUrl().'/#performer',
                'name' => $name,
                'url' => Seo::baseUrl().'/',
                'sameAs' => self::brandSameAs(),
            ], fn ($v) => filled($v));
        }, $event->performerNames());
    }

    /**
     * De social-profielen uit de footer, als sameAs-lijst. Gedeeld door de
     * LocalBusiness- en de performer-node — één bron voor entiteitsherkenning.
     *
     * @return array<int, string>
     */
    private static function brandSameAs(): array
    {
        $social = SiteFooter::current()['social'] ?? [];

        return array_values(array_filter([
            $social['facebook'] ?? null,
            $social['instagram'] ?? null,
            $social['youtube'] ?? null,
        ], fn ($v) => filled($v)));
    }

    /**
     * Sitemap-regels na de pagina's: eventoverzicht (in elke taal), alle
     * gepubliceerde events en de mixtape-detailpagina's (taal-onafhankelijk:
     * alternates in elke taal).
     *
     * @return array<int, array<string, mixed>>
     */
    public static function sitemapEntries(): array
    {
        $urls = [];

        $indexAlternates = [];
        foreach (Locale::supported() as $locale) {
            $indexAlternates[$locale] = Seo::absoluteUrl(Locale::href('/events', $locale));
        }
        $indexAlternates['x-default'] = $indexAlternates[Locale::defaultLocale()];

        $events = Event::query()->published()->with('translations')->get();

        $urls[] = [
            'loc' => $indexAlternates[Locale::defaultLocale()],
            'lastmod' => $events->max('updated_at'),
            'priority' => '0.8',
            'alternates' => $indexAlternates,
        ];

        foreach ($events as $event) {
            $alternates = self::eventAlternates($event);
            $alternates['x-default'] = $alternates[Locale::defaultLocale()];

            $urls[] = [
                'loc' => $event->publicUrl(Locale::defaultLocale()),
                'lastmod' => $event->updated_at,
                'priority' => '0.6',
                'alternates' => $alternates,
            ];
        }

        foreach (Mixtape::query()->published()->ordered()->get() as $mixtape) {
            $alternates = [];
            foreach (Locale::supported() as $locale) {
                $alternates[$locale] = $mixtape->publicUrl($locale);
            }
            $alternates['x-default'] = $alternates[Locale::defaultLocale()];

            $urls[] = [
                'loc' => $mixtape->publicUrl(Locale::defaultLocale()),
                'lastmod' => $mixtape->updated_at,
                'priority' => '0.5',
                'alternates' => $alternates,
            ];
        }

        return $urls;
    }

    /**
     * llms.txt-sectie "Events" (aankomend, niet afgelast). De core zet zelf een
     * lege regel achter het geheel.
     *
     * @return array<int, string>
     */
    public static function llmsLines(): array
    {
        $events = Event::query()
            ->published()
            ->upcoming()
            ->notCancelled()
            ->orderBy('start_date')
            ->get();

        if ($events->isEmpty()) {
            return [];
        }

        $lines = ['## Events'];
        foreach ($events as $event) {
            $desc = filled($event->short_description) ? ': '.$event->short_description : '';
            $lines[] = '- ['.$event->name.' ('.$event->dateLabel().')]('.$event->publicUrl(Locale::defaultLocale()).')'.$desc;
        }

        return $lines;
    }
}
