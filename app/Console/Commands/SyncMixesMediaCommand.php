<?php

namespace App\Console\Commands;

use App\Models\Page;
use App\Models\PageSection;
use App\Support\Locale;
use Illuminate\Console\Command;

/**
 * Kopieert de mp3's en covers van de Nederlandse mixes-secties naar hun EN/ES-
 * vertalingen.
 *
 * Waarom nodig: elke taal is een eigen pagina met eigen secties, dus een mp3 die
 * je op de NL-pagina uploadt komt niet vanzelf op /en en /es terecht. Media zijn
 * taal-onafhankelijk (dezelfde set, dezelfde cover) — enkel de subtitel verschilt,
 * en die blijft hier bewust staan.
 *
 * De Nederlandse pagina is de bron: items die daar niet (meer) bestaan, worden ook
 * uit de vertalingen gehaald.
 *
 * Draai dit telkens nadat je in NL een mix hebt toegevoegd, vervangen of verwijderd.
 */
class SyncMixesMediaCommand extends Command
{
    protected $signature = 'mixes:sync-media {--dry-run : Toon enkel wat er zou wijzigen}';

    protected $description = 'Kopieer audio en covers van de NL-mixes naar de EN/ES-vertalingen';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $changed = 0;

        $sourcePages = Page::query()
            ->where('locale', Locale::DEFAULT)
            ->with('sections')
            ->get();

        foreach ($sourcePages as $source) {
            $sourceSection = $source->sections->firstWhere('section_type', 'mixes');

            if ($sourceSection === null) {
                continue;
            }

            $sourceItems = $sourceSection->content['items'] ?? [];

            // Vertalingen delen de slug; translation_of is de expliciete koppeling.
            $translations = Page::query()
                ->where('locale', '!=', Locale::DEFAULT)
                ->where(function ($query) use ($source) {
                    $query->where('translation_of', $source->id)
                        ->orWhere('slug', $source->slug);
                })
                ->with('sections')
                ->get();

            foreach ($translations as $translation) {
                $targetSection = $translation->sections->firstWhere('section_type', 'mixes');

                if ($targetSection === null) {
                    continue;
                }

                $merged = $this->mergeItems($sourceItems, $targetSection->content['items'] ?? []);
                $content = $targetSection->content;

                if (($content['items'] ?? []) == $merged) {
                    continue;
                }

                $before = count($content['items'] ?? []);
                $content['items'] = $merged;

                $this->line(sprintf(
                    '  %s/%s: %d -> %d items, media overgenomen van %s',
                    $translation->locale,
                    $translation->slug,
                    $before,
                    count($merged),
                    Locale::DEFAULT,
                ));

                if (! $dryRun) {
                    $this->persist($targetSection, $content);
                }

                $changed++;
            }
        }

        if ($changed === 0) {
            $this->info('Alle vertalingen staan al gelijk.');

            return self::SUCCESS;
        }

        $this->newLine();
        $this->info($dryRun
            ? "{$changed} sectie(s) zouden wijzigen. Draai zonder --dry-run om door te voeren."
            : "{$changed} sectie(s) bijgewerkt.");

        return self::SUCCESS;
    }

    /**
     * De NL-items zijn leidend voor volgorde, titel en media. Bestaat er al een
     * vertaald item met dezelfde titel, dan houden we zijn subtitel — dat is het
     * enige veld dat per taal verschilt.
     */
    private function mergeItems(array $sourceItems, array $targetItems): array
    {
        $subtitles = [];

        foreach ($targetItems as $item) {
            $title = $item['title'] ?? null;

            if (filled($title) && filled($item['subtitle'] ?? null)) {
                $subtitles[$title] = $item['subtitle'];
            }
        }

        return array_map(function (array $item) use ($subtitles): array {
            $title = $item['title'] ?? null;

            if ($title !== null && isset($subtitles[$title])) {
                $item['subtitle'] = $subtitles[$title];
            }

            return $item;
        }, array_values($sourceItems));
    }

    /**
     * De repeater-items in de builder hebben UUID-sleutels; die worden bij het
     * opslaan opnieuw gegenereerd, dus een simpele overschrijving volstaat.
     */
    private function persist(PageSection $section, array $content): void
    {
        $section->content = $content;
        $section->save();
    }
}
