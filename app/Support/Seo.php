<?php

namespace App\Support;

use App\Models\Event;
use App\Models\Mixtape;
use App\Models\Page;
use App\Models\WebsiteMedia;
use Illuminate\Support\Str;

/**
 * Centrale SEO/GEO-helper. Levert per pagina een genormaliseerde bundel
 * meta-velden (titel, beschrijving, canonical, robots, og-afbeelding, og-type)
 * plus de bijhorende JSON-LD-nodes, én de site-brede structured data
 * (LocalBusiness + WebSite, gevoed door de footer-instellingen).
 *
 * Eén waarheidsbron zodat de <head> (components/site/meta.blade.php) enkel nog
 * hoeft te renderen wat hier berekend is.
 *
 * TODO (per project): zet DEFAULT_IMAGE op een echte standaard-deelafbeelding en
 * defaultDescription() op een wervende sitebrede beschrijving (of lees ze uit
 * een Setting).
 */
class Seo
{
    public const LOCALE = 'nl_BE';

    public const TIMEZONE = 'Europe/Brussels';

    /** Open Graph-locale per taal. */
    public const OG_LOCALES = ['nl' => 'nl_BE', 'en' => 'en_US', 'es' => 'es_ES'];

    /** BCP-47 taalcode per taal (voor <html lang> en JSON-LD inLanguage). */
    public const HTML_LANGS = ['nl' => 'nl-BE', 'en' => 'en', 'es' => 'es'];

    public static function ogLocale(?string $locale): string
    {
        return self::OG_LOCALES[$locale] ?? self::LOCALE;
    }

    public static function htmlLang(?string $locale): string
    {
        return self::HTML_LANGS[$locale] ?? 'nl-BE';
    }

    /** Standaard deel-afbeelding wanneer een pagina er zelf geen heeft. */
    public const DEFAULT_IMAGE = null;

    public static function siteName(): string
    {
        return (string) config('app.name');
    }

    /**
     * De merknaam zoals de bezoeker hem kent: de naam uit de footer-instellingen,
     * met de app-naam als terugval. Eén bron voor de LocalBusiness-node én voor
     * de standaard-performer op een event.
     */
    public static function brandName(): string
    {
        $name = SiteFooter::current()['brand']['name'] ?? null;

        return filled($name) ? (string) $name : self::siteName();
    }

    public static function defaultDescription(): string
    {
        return '';
    }

    /**
     * Maak een relatieve URL absoluut (vereist voor og:image, canonical, JSON-LD).
     * Volledige URLs blijven ongemoeid.
     */
    public static function absoluteUrl(?string $url): ?string
    {
        if (blank($url)) {
            return null;
        }

        if (Str::startsWith($url, ['http://', 'https://'])) {
            return $url;
        }

        return rtrim((string) config('app.url'), '/').'/'.ltrim($url, '/');
    }

    /** De basis-URL van de site, zonder trailing slash. */
    public static function baseUrl(): string
    {
        return rtrim((string) config('app.url'), '/');
    }

    /**
     * Meta-bundel + JSON-LD voor een CMS-pagina.
     *
     * @return array<string, mixed>
     */
    public static function fromPage(Page $page): array
    {
        $locale = $page->locale ?? Locale::DEFAULT;

        $canonical = filled($page->canonical_url)
            ? $page->canonical_url
            : self::absoluteUrl(self::localizedPath($page, $locale));

        $title = filled($page->meta_title) ? $page->meta_title : $page->title;
        $description = filled($page->meta_description) ? $page->meta_description : self::defaultDescription();

        [$image, $imageAlt, $width, $height] = self::resolvePageImage($page);

        $faqNode = self::faqNode($page, $canonical);

        $node = array_filter([
            '@type' => 'WebPage',
            '@id' => $canonical.'#webpage',
            'url' => $canonical,
            'name' => $title,
            'description' => $description,
            'isPartOf' => ['@id' => self::baseUrl().'/#website'],
            'inLanguage' => self::htmlLang($locale),
            'primaryImageOfPage' => $image
                ? ['@type' => 'ImageObject', 'url' => self::absoluteUrl($image)]
                : null,
        ], fn ($v) => filled($v));

        return [
            'title' => $title,
            'description' => $description,
            'canonical' => $canonical,
            'robots' => filled($page->meta_robots) ? $page->meta_robots : 'index, follow',
            'image' => $image,
            'imageAlt' => $imageAlt,
            'imageWidth' => $width,
            'imageHeight' => $height,
            'type' => 'website',
            'locale' => $locale,
            'alternates' => self::alternates($page),
            'schema' => array_values(array_filter([$node, $faqNode])),
        ];
    }

    /**
     * Root-relatief pad van een pagina in een bepaalde taal (met locale-prefix
     * voor EN/ES). Homepage = '/'.
     */
    public static function localizedPath(Page $page, ?string $locale = null): string
    {
        $locale ??= $page->locale ?? Locale::DEFAULT;
        $path = $page->is_homepage ? '/' : '/'.$page->slug;

        return Locale::href($path, $locale);
    }

    /**
     * hreflang-alternates voor een pagina: absolute URL per taal waarin een
     * publieke variant met dezelfde slug bestaat. Voedt zowel de <head>-hreflang
     * als de sitemap.
     *
     * @return array<string, string> locale => absolute URL
     */
    public static function alternates(Page $page): array
    {
        $locales = Page::query()
            ->where('slug', $page->slug)
            ->where(fn ($q) => $q->where('published', true)->orWhere('is_homepage', true))
            ->pluck('locale')
            ->all();

        $result = [];
        foreach ($locales as $locale) {
            $result[$locale] = self::absoluteUrl(self::localizedPath($page, $locale));
        }

        return $result;
    }

    /**
     * Meta-bundel + JSON-LD voor een event-detailpagina, in een specifieke taal.
     *
     * @return array<string, mixed>
     */
    public static function fromEvent(Event $event, string $locale): array
    {
        $canonical = self::absoluteUrl($event->localizedPath($locale));

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
        $canonical = self::absoluteUrl(Locale::href('/events', $locale));

        $alternates = [];
        foreach (Locale::supported() as $alt) {
            $alternates[$alt] = self::absoluteUrl(Locale::href('/events', $alt));
        }

        $title = __('Events');
        $description = __('Alle events van :name: data, locaties en tickets.', ['name' => self::siteName()]);

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
                'isPartOf' => ['@id' => self::baseUrl().'/#website'],
                'inLanguage' => self::htmlLang($locale),
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
        $canonical = self::absoluteUrl($mixtape->localizedPath($locale));

        $description = filled($mixtape->subtitle)
            ? $mixtape->subtitle
            : __('Beluister de mixtape :title van :name.', ['title' => $mixtape->title, 'name' => self::siteName()]);

        $dimensions = filled($mixtape->cover_url) ? WebsiteMedia::dimensionsForUrl($mixtape->cover_url) : [];

        $alternates = [];
        foreach (Locale::supported() as $alt) {
            $alternates[$alt] = self::absoluteUrl($mixtape->localizedPath($alt));
        }

        $schema = [
            '@type' => 'MusicPlaylist',
            '@id' => $canonical.'#mixtape',
            'url' => $canonical,
            'name' => $mixtape->title,
            'description' => $description,
            'isPartOf' => ['@id' => self::baseUrl().'/#website'],
        ];

        if (filled($mixtape->cover_url)) {
            $schema['image'] = self::absoluteUrl($mixtape->cover_url);
        }

        if ($audio = $mixtape->resolvedAudioUrl()) {
            $schema['audio'] = [
                '@type' => 'AudioObject',
                'contentUrl' => self::absoluteUrl($audio),
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
        $result = [Locale::DEFAULT => self::absoluteUrl($event->localizedPath(Locale::DEFAULT))];

        foreach (Locale::supported() as $locale) {
            if ($locale === Locale::DEFAULT) {
                continue;
            }

            if ($event->translationFor($locale)?->hasContent()) {
                $result[$locale] = self::absoluteUrl($event->localizedPath($locale));
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
                ->shiftTimezone(self::TIMEZONE)
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
            $endDate = $end->shiftTimezone(self::TIMEZONE)->format('Y-m-d\TH:i:sP');
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
            'image' => self::absoluteUrl($event->image_url),
            'performer' => self::performerNodes($event),
            'organizer' => ['@id' => self::baseUrl().'/#business'],
            'offers' => $offers !== [] ? $offers : null,
            'inLanguage' => self::htmlLang($locale),
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
        $brand = self::brandName();

        return array_map(function (string $name) use ($brand): array {
            if ($name !== $brand) {
                return ['@type' => 'Person', 'name' => $name];
            }

            return array_filter([
                '@type' => 'Person',
                '@id' => self::baseUrl().'/#performer',
                'name' => $name,
                'url' => self::baseUrl().'/',
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
     * Site-brede JSON-LD die op élke pagina meegaat: het bedrijf (LocalBusiness)
     * en de website zelf. Gevoed door de footer-instellingen zodat NAP-gegevens
     * (naam, adres, telefoon, e-mail, social) één bron hebben.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function globalGraph(): array
    {
        $footer = SiteFooter::current();
        $contact = $footer['contact'] ?? [];
        $brand = $footer['brand'] ?? [];
        $base = self::baseUrl();
        $sameAs = self::brandSameAs();

        $business = array_filter([
            '@type' => 'LocalBusiness',
            '@id' => $base.'/#business',
            'name' => self::brandName(),
            'url' => $base.'/',
            'logo' => self::absoluteUrl($brand['logo'] ?? null),
            'telephone' => $contact['phone'] ?? null,
            'email' => $contact['email'] ?? null,
            'address' => self::parseAddress($contact['address'] ?? ''),
            'sameAs' => $sameAs !== [] ? $sameAs : null,
        ], fn ($v) => filled($v));

        $website = [
            '@type' => 'WebSite',
            '@id' => $base.'/#website',
            'url' => $base.'/',
            'name' => self::siteName(),
            'inLanguage' => 'nl-BE',
            'publisher' => ['@id' => $base.'/#business'],
        ];

        return [$business, $website];
    }

    /**
     * Encodeer een lijst JSON-LD-nodes tot een schema.org-document. Bewust hier
     * (niet inline in Blade): de letterlijke string "@context" zou anders door de
     * Blade-compiler als de @context-directive verwerkt worden.
     *
     * @param  array<int, array<string, mixed>>  $graph
     */
    public static function jsonLd(array $graph): string
    {
        return (string) json_encode(
            ['@context' => 'https://schema.org', '@graph' => array_values($graph)],
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
        );
    }

    /**
     * Bouw een FAQPage-node uit alle `faq`-secties van een pagina. Sterk voor
     * zowel rich results als GEO. Antwoorden worden tot platte tekst herleid.
     *
     * @return array<string, mixed>|null
     */
    private static function faqNode(Page $page, string $canonical): ?array
    {
        $questions = [];

        foreach ($page->sections->where('section_type', 'faq') as $section) {
            foreach ($section->content['items'] ?? [] as $item) {
                $question = trim((string) ($item['question'] ?? ''));
                $answer = Str::of($item['answer'] ?? '')->stripTags()->squish()->value();

                if ($question !== '' && $answer !== '') {
                    $questions[] = [
                        '@type' => 'Question',
                        'name' => $question,
                        'acceptedAnswer' => ['@type' => 'Answer', 'text' => $answer],
                    ];
                }
            }
        }

        if ($questions === []) {
            return null;
        }

        return [
            '@type' => 'FAQPage',
            '@id' => $canonical.'#faq',
            'mainEntity' => $questions,
        ];
    }

    /**
     * Resolve de deel-afbeelding van een pagina: expliciete SEO-afbeelding →
     * eerste hero-afbeelding → site-default. De SEO-afbeelding is een URL-string
     * (zoals alle media-velden); dimensies leiden we af uit de media-tabel.
     *
     * @return array{0: ?string, 1: ?string, 2: ?int, 3: ?int}
     */
    private static function resolvePageImage(Page $page): array
    {
        if (filled($page->seo_image_url)) {
            $dimensions = WebsiteMedia::dimensionsForUrl($page->seo_image_url);

            return [
                $page->seo_image_url,
                filled($page->seo_image_alt) ? $page->seo_image_alt : $page->title,
                $dimensions['width'] ?? null,
                $dimensions['height'] ?? null,
            ];
        }

        $hero = $page->sections->firstWhere('section_type', 'hero');
        $heroSrc = $hero?->content['image']['src'] ?? null;

        if (filled($heroSrc)) {
            return [$heroSrc, $hero->content['image']['alt'] ?? $page->title, null, null];
        }

        return [self::DEFAULT_IMAGE, $page->title, null, null];
    }

    /**
     * Parse het meerregelige footer-adres naar een schema.org PostalAddress.
     * Verwacht "straat + nr" op regel 1 en "postcode gemeente" op regel 2.
     *
     * @return array<string, string>|null
     */
    private static function parseAddress(string $raw): ?array
    {
        $lines = array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $raw))));

        if ($lines === []) {
            return null;
        }

        $address = [
            '@type' => 'PostalAddress',
            'streetAddress' => $lines[0],
            'addressCountry' => 'BE',
        ];

        if (isset($lines[1])) {
            if (preg_match('/^(\d{4})\s+(.+)$/', $lines[1], $m)) {
                $address['postalCode'] = $m[1];
                $address['addressLocality'] = $m[2];
            } else {
                $address['addressLocality'] = $lines[1];
            }
        }

        return $address;
    }
}
