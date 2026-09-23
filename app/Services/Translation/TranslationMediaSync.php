<?php

namespace App\Services\Translation;

use App\Models\Page;

/**
 * Houdt de media van vertaalde pagina's gelijk met het NL-origineel.
 *
 * Een vertaling is een kopie van de bron op het moment van vertalen. Zonder
 * deze sync bleef een EN/ES-pagina de oude foto tonen zodra je in het NL een
 * afbeelding verving — tot je opnieuw "Vertalen met AI" deed. Media zijn niet
 * taalgebonden, dus ze volgen de bron altijd; tekst blijft ongemoeid.
 *
 * Secties worden gekoppeld op positie + sectietype, niet op `translation_of`:
 * het opslaan van een pagina maakt haar secties opnieuw aan (nieuwe id's), dus
 * die verwijzing is na de eerste bewerking niet meer betrouwbaar. Wijkt de
 * opbouw af (sectie toegevoegd, verwijderd of verschoven), dan wordt die sectie
 * overgeslagen — daarvoor is een nieuwe AI-vertaling de juiste weg.
 */
class TranslationMediaSync
{
    /**
     * Sleutels die een media-URL bevatten, waar ze ook in de sectie-content
     * staan (MediaPickerField/AudioPickerField + video-links).
     *
     * @var array<int, string>
     */
    public const MEDIA_KEYS = ['src', 'image', 'image_url', 'video_url', 'cover_url'];

    public function sync(Page $source): void
    {
        if ($source->translation_of !== null) {
            return;
        }

        $source->loadMissing('sections');

        foreach ($source->translations()->with('sections')->get() as $translation) {
            if ($translation->seo_image_url !== $source->seo_image_url) {
                $translation->update(['seo_image_url' => $source->seo_image_url]);
            }

            $targets = $translation->sections->keyBy('position');

            foreach ($source->sections as $section) {
                $target = $targets->get($section->position);

                if ($target === null || $target->section_type !== $section->section_type) {
                    continue;
                }

                $content = $target->content ?? [];
                $this->copyMedia($section->content ?? [], $content);

                if ($content !== ($target->content ?? [])) {
                    $target->update(['content' => $content]);
                }
            }
        }
    }

    /**
     * Neem de media-waarden van $source over in $target, op dezelfde paden.
     * Heeft een lijst (galerij, foto's naast tekst) in de bron méér items dan
     * in de vertaling, dan komen de nieuwe items er integraal bij — anders zou
     * een foto die je in het NL toevoegt nooit in de andere talen verschijnen.
     */
    private function copyMedia(array $source, array &$target): void
    {
        foreach ($source as $key => $value) {
            if (is_array($value)) {
                if (! array_key_exists($key, $target) || ! is_array($target[$key])) {
                    if (is_int($key)) {
                        $target[$key] = $value;
                    }

                    continue;
                }

                $this->copyMedia($value, $target[$key]);

                continue;
            }

            if (is_string($key) && in_array($key, self::MEDIA_KEYS, true)) {
                $target[$key] = $value;
            }
        }
    }
}
