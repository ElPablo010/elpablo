<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Page;
use App\Support\Locale;
use App\Support\Seo;
use App\Support\SiteFooter;
use Illuminate\Http\Response;

/**
 * Dynamische SEO/GEO-assets die de live database weerspiegelen:
 * - /sitemap.xml  — alle publieke pagina's
 * - /robots.txt   — verwijst naar de sitemap met de juiste host per omgeving
 * - /llms.txt     — gestructureerd overzicht voor AI-zoekmachines (GEO)
 *
 * Breid sitemap()/llms() per project uit met domein-content (events, producten…).
 */
class SeoController extends Controller
{
    public function sitemap(): Response
    {
        // Alle publieke pagina's over álle talen (homepage altijd mee). Per pagina
        // voegen we de hreflang-alternates toe zodat zoekmachines de taalversies
        // koppelen i.p.v. ze als duplicaten te zien.
        $pages = Page::query()
            ->where(fn ($q) => $q->where('published', true)->orWhere('is_homepage', true))
            ->where(fn ($q) => $q->whereNull('meta_robots')->orWhere('meta_robots', 'not like', '%noindex%'))
            ->get();

        $urls = [];

        foreach ($pages as $page) {
            $alternates = Seo::alternates($page);

            // x-default wijst naar de hoofdtaal (NL).
            if (isset($alternates['nl'])) {
                $alternates['x-default'] = $alternates['nl'];
            }

            $urls[] = [
                'loc' => Seo::absoluteUrl(Seo::localizedPath($page)),
                'lastmod' => $page->updated_at,
                'priority' => $page->is_homepage ? '1.0' : ($page->is_cornerstone ? '0.8' : '0.6'),
                'alternates' => $alternates,
            ];
        }

        // Eventoverzicht (bestaat in elke taal) + alle gepubliceerde events.
        $indexAlternates = [];
        foreach (Locale::supported() as $locale) {
            $indexAlternates[$locale] = Seo::absoluteUrl(Locale::href('/events', $locale));
        }
        $indexAlternates['x-default'] = $indexAlternates[Locale::DEFAULT];

        $events = Event::query()->published()->with('translations')->get();

        $urls[] = [
            'loc' => $indexAlternates[Locale::DEFAULT],
            'lastmod' => $events->max('updated_at'),
            'priority' => '0.8',
            'alternates' => $indexAlternates,
        ];

        foreach ($events as $event) {
            $alternates = Seo::eventAlternates($event);
            $alternates['x-default'] = $alternates[Locale::DEFAULT];

            $urls[] = [
                'loc' => $event->publicUrl(Locale::DEFAULT),
                'lastmod' => $event->updated_at,
                'priority' => '0.6',
                'alternates' => $alternates,
            ];
        }

        // Mixtape-detailpagina's (taal-onafhankelijk: alternates in elke taal).
        foreach (\App\Models\Mixtape::query()->published()->ordered()->get() as $mixtape) {
            $alternates = [];
            foreach (Locale::supported() as $locale) {
                $alternates[$locale] = $mixtape->publicUrl($locale);
            }
            $alternates['x-default'] = $alternates[Locale::DEFAULT];

            $urls[] = [
                'loc' => $mixtape->publicUrl(Locale::DEFAULT),
                'lastmod' => $mixtape->updated_at,
                'priority' => '0.5',
                'alternates' => $alternates,
            ];
        }

        return response()
            ->view('sitemap', ['urls' => $urls])
            ->header('Content-Type', 'application/xml');
    }

    public function robots(): Response
    {
        $body = implode("\n", [
            'User-agent: *',
            'Disallow: /admin',
            'Disallow: /livewire',
            '',
            'Sitemap: '.Seo::baseUrl().'/sitemap.xml',
            '',
        ]);

        return response($body, 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }

    public function llms(): Response
    {
        $footer = SiteFooter::current();
        $contact = $footer['contact'] ?? [];
        $base = Seo::baseUrl();

        $lines = [];
        $lines[] = '# '.Seo::siteName();
        $lines[] = '';

        $lines[] = '## Contact';
        if (filled($contact['address'] ?? null)) {
            $lines[] = '- Adres: '.str_replace(["\r\n", "\r", "\n"], ', ', trim($contact['address']));
        }
        if (filled($contact['phone'] ?? null)) {
            $lines[] = '- Telefoon: '.$contact['phone'];
        }
        if (filled($contact['email'] ?? null)) {
            $lines[] = '- E-mail: '.$contact['email'];
        }
        $lines[] = '- Website: '.$base.'/';
        $lines[] = '';

        $pages = Page::query()
            ->where('locale', 'nl')
            ->where(fn ($q) => $q->where('published', true)->orWhere('is_homepage', true))
            ->where(fn ($q) => $q->whereNull('meta_robots')->orWhere('meta_robots', 'not like', '%noindex%'))
            ->orderByDesc('is_homepage')
            ->orderByDesc('is_cornerstone')
            ->get();

        if ($pages->isNotEmpty()) {
            $lines[] = '## Pagina\'s';
            foreach ($pages as $page) {
                $url = Seo::absoluteUrl($page->is_homepage ? '/' : '/'.$page->slug);
                $desc = filled($page->meta_description) ? ': '.$page->meta_description : '';
                $lines[] = '- ['.($page->meta_title ?: $page->title).']('.$url.')'.$desc;
            }
            $lines[] = '';
        }

        $events = Event::query()
            ->published()
            ->upcoming()
            ->notCancelled()
            ->orderBy('start_date')
            ->get();

        if ($events->isNotEmpty()) {
            $lines[] = '## Events';
            foreach ($events as $event) {
                $desc = filled($event->short_description) ? ': '.$event->short_description : '';
                $lines[] = '- ['.$event->name.' ('.$event->dateLabel().')]('.$event->publicUrl(Locale::DEFAULT).')'.$desc;
            }
            $lines[] = '';
        }

        return response(implode("\n", $lines), 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }
}
