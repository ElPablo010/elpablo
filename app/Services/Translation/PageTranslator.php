<?php

namespace App\Services\Translation;

use App\Models\Page;
use App\Models\PageSection;
use App\Services\Translation\Concerns\TranslatesContentArrays;

/**
 * Vertaalt een volledige pagina — de pagina-velden én de JSON-content van al
 * haar secties — naar een andere taal, en bewaart het resultaat als een aparte
 * pagina die via `translation_of` aan het origineel hangt.
 *
 * De aanpak: alle vertaalbare tekst uit de sectie-content platslaan tot één
 * [pad => tekst]-map, die in één keer laten vertalen, en de antwoorden op
 * exact dezelfde paden terugzetten. Structuur, volgorde, media en instellingen
 * blijven daardoor gegarandeerd intact — enkel tekst verandert.
 *
 * Wat níét mee vertaald wordt, staat in de skip-lijst van de gedeelde
 * TranslatesContentArrays-trait: dat zijn de velden die geen tekst bevatten
 * maar structuur (kleur, uitlijning, kolommen), verwijzingen (page_id,
 * media-URL's) of getallen. Vertaal je die per ongeluk mee, dan breekt de
 * sectie stil — vandaar dat die lijst de belangrijkste is om bij te werken
 * wanneer er een nieuw sectietype bijkomt.
 */
class PageTranslator
{
    use TranslatesContentArrays;

    public function __construct(private readonly ClaudeTranslator $translator) {}

    /**
     * Vertaal een pagina naar $toLocale. Bestaat de vertaling al, dan wordt ze
     * bijgewerkt (zelfde id en slug blijven behouden, zodat bestaande links en
     * de opgebouwde SEO-waarde van die URL niet sneuvelen).
     */
    public function translate(Page $page, string $toLocale): Page
    {
        // Werk altijd vanaf het origineel: vertaal je per ongeluk een vertaling,
        // dan zou een dubbele vertaalslag de tekst alleen maar verder laten
        // afdrijven van wat de klant geschreven heeft.
        $source = $page->translation_of !== null ? $page->sourceTranslation : $page;
        $source->loadMissing('sections');

        $fromLocale = $source->locale;
        $target = $source->translations()->where('locale', $toLocale)->first();

        $sections = $source->sections
            ->map(fn (PageSection $section) => [
                'source_id' => $section->id,
                'section_type' => $section->section_type,
                'position' => $section->position,
                'content' => $section->content ?? [],
            ])
            ->values()
            ->all();

        $texts = $this->collectTexts($source, $sections);

        $translated = $texts === []
            ? []
            : $this->translator->translate(
                $texts,
                $fromLocale,
                $toLocale,
                context: 'Web page titled "'.$source->title.'".',
            );

        $target = $this->persistPage($source, $target, $toLocale, $translated);
        $this->persistSections($target, $sections, $toLocale, $translated);

        return $target->refresh();
    }

    /**
     * Alle vertaalbare tekst van pagina + secties, als [pad => tekst].
     * Paden: `page.title` voor pagina-velden, `s3.heading` voor sectie 3.
     *
     * @param  array<int, array<string, mixed>>  $sections
     * @return array<string, string>
     */
    private function collectTexts(Page $source, array $sections): array
    {
        $texts = [];

        foreach (['title', 'meta_title', 'meta_description', 'seo_image_alt'] as $field) {
            if (filled($source->{$field})) {
                $texts["page.{$field}"] = (string) $source->{$field};
            }
        }

        foreach ($sections as $index => $section) {
            $extracted = [];
            $this->extract($section['content'], $extracted);

            foreach ($extracted as $path => $text) {
                $texts["s{$index}.{$path}"] = $text;
            }
        }

        return $texts;
    }

    /**
     * Maak of werk de vertaalde pagina bij.
     *
     * @param  array<string, string>  $translated
     */
    private function persistPage(Page $source, ?Page $target, string $toLocale, array $translated): Page
    {
        $title = $translated['page.title'] ?? $source->title;

        $attributes = [
            'title' => $title,
            'meta_title' => $translated['page.meta_title'] ?? $source->meta_title,
            'meta_description' => $translated['page.meta_description'] ?? $source->meta_description,
            'seo_image_alt' => $translated['page.seo_image_alt'] ?? $source->seo_image_alt,

            // Niet-tekstuele velden volgen het origineel: de klant beheert die
            // op één plek en verwacht niet dat ze per taal uit elkaar lopen.
            // Expliciet naar bool: een vers aangemaakte bron zonder refresh
            // draagt deze attributen als null, en de kolommen zijn NOT NULL.
            'is_homepage' => (bool) $source->is_homepage,
            'published' => (bool) $source->published,
            'meta_robots' => $source->meta_robots,
            'is_cornerstone' => (bool) $source->is_cornerstone,
            'seo_image_url' => $source->seo_image_url,
            'canonical_url' => null,
        ];

        if ($target !== null) {
            // Slug bewust ongemoeid laten: die URL is mogelijk al gedeeld of
            // geïndexeerd, en de klant kan hem handmatig aangepast hebben.
            $target->fill($attributes)->save();

            return $target;
        }

        return Page::create([
            ...$attributes,
            'locale' => $toLocale,
            'translation_of' => $source->id,
            // Gedeelde slug per taal (unique op [locale, slug]): een interne
            // link wordt gelokaliseerd door enkel de prefix (Locale::href),
            // dus de vertaling MOET dezelfde slug dragen als de bron.
            'slug' => $source->slug,
        ]);
    }

    /**
     * Schrijf de vertaalde secties weg. De bestaande secties van de vertaling
     * worden vervangen: de volgorde en samenstelling van het origineel is
     * leidend, anders lopen de twee talen uit elkaar bij elke herhaling.
     *
     * @param  array<int, array<string, mixed>>  $sections
     * @param  array<string, string>  $translated
     */
    private function persistSections(Page $target, array $sections, string $toLocale, array $translated): void
    {
        $target->sections()->delete();

        foreach ($sections as $index => $section) {
            $content = $section['content'];

            foreach ($translated as $path => $value) {
                if (! str_starts_with($path, "s{$index}.")) {
                    continue;
                }

                $this->setAtPath($content, substr($path, strlen("s{$index}.")), $value);
            }

            // Geen link-remapping: verwijzingen (page_id, href) blijven
            // NL-vormig en worden bij het renderen gelokaliseerd via Locale::href.
            $target->sections()->create([
                'section_type' => $section['section_type'],
                'position' => $section['position'],
                'content' => $content,
                'locale' => $toLocale,
                'translation_of' => $section['source_id'],
            ]);
        }
    }
}
