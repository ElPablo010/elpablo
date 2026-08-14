<?php

namespace App\Filament\Concerns;

use App\Models\Event;
use App\Support\Locale;

/**
 * Gedeelde vorm-logica voor de Create- en Edit-pagina van events:
 *
 * - **Vertalingen** (EN/ES) leven in event_translations, niet op het event
 *   zelf. Het formulier toont ze onder statePath `translations.{locale}`; deze
 *   trait laadt ze bij het vullen en bewaart ze ná het opslaan van het event.
 * - **Afgelast** is in het formulier een toggle (`is_cancelled`), in de
 *   database een timestamp (`cancelled_at`). De omzetting gebeurt hier; de
 *   annuleringsboodschap blijft bewust staan bij het ongedaan maken, zodat ze
 *   bij een her-annulering niet opnieuw getikt hoeft te worden.
 */
trait ManagesEventFormData
{
    /** @var array<string, array<string, ?string>> */
    protected array $pendingTranslations = [];

    protected function fillEventFormData(array $data, ?Event $event): array
    {
        $data['is_cancelled'] = filled($data['cancelled_at'] ?? null);

        foreach (Locale::supported() as $locale) {
            if ($locale === Locale::DEFAULT) {
                continue;
            }

            $translation = $event?->translationFor($locale);
            $data['translations'][$locale] = [
                'name' => $translation?->name,
                'short_description' => $translation?->short_description,
                'description' => $translation?->description,
            ];
        }

        return $data;
    }

    protected function extractEventFormData(array $data, ?Event $event): array
    {
        $this->pendingTranslations = array_map(
            fn (array $fields): array => array_map(self::normalizeRichText(...), $fields),
            $data['translations'] ?? [],
        );
        unset($data['translations']);

        if (array_key_exists('description', $data)) {
            $data['description'] = self::normalizeRichText($data['description']);
        }

        $wasCancelled = $event?->cancelled_at !== null;
        $isCancelled = (bool) ($data['is_cancelled'] ?? false);
        unset($data['is_cancelled']);

        if ($isCancelled && ! $wasCancelled) {
            $data['cancelled_at'] = now();
        } elseif (! $isCancelled) {
            $data['cancelled_at'] = null;
        }

        return $data;
    }

    protected function persistEventTranslations(Event $event): void
    {
        foreach ($this->pendingTranslations as $locale => $fields) {
            $event->translations()->updateOrCreate(['locale' => $locale], $fields);
        }
    }

    /**
     * Een onaangeraakte RichEditor levert "<p></p>" op, geen null. Zonder
     * normalisatie zou elke lege vertaling als "inhoud" tellen (hasContent) en
     * in de taalwissel/hreflang opduiken terwijl er niets vertaald is.
     */
    protected static function normalizeRichText(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $text = trim(str_replace("\u{A0}", ' ', strip_tags($value)));

        return $text === '' ? null : $value;
    }
}
