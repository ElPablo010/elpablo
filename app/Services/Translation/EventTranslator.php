<?php

namespace App\Services\Translation;

use App\Models\Event;
use App\Support\Locale;

/**
 * Vertaalt de tekstvelden van een event naar de rijen in `event_translations`.
 * Anders dan pagina's heeft een event géén eigen rij per taal — voorraad en
 * planning zijn gedeeld; enkel naam en beschrijvingen bestaan per taal (zie
 * het events-model in CLAUDE.md).
 */
class EventTranslator
{
    public function __construct(private readonly ClaudeTranslator $translator) {}

    /**
     * Vertaal een event naar $toLocale en bewaar (of overschrijf) de
     * bijbehorende event_translations-rij.
     */
    public function translate(Event $event, string $toLocale): void
    {
        $texts = array_filter([
            'name' => $event->name,
            'short_description' => $event->short_description,
            'description' => $event->description,
        ], fn ($value) => filled($value));

        if ($texts === []) {
            throw new TranslationException('Dit event heeft geen tekst om te vertalen.');
        }

        $translated = $this->translator->translate(
            $texts,
            Locale::DEFAULT,
            $toLocale,
            context: 'Event page for an Urban Latin DJ (name, short teaser, full description). The description may contain HTML — keep all tags and attributes exactly as they are.',
        );

        $event->translations()->updateOrCreate(
            ['locale' => $toLocale],
            [
                'name' => $translated['name'] ?? $event->name,
                'short_description' => $translated['short_description'] ?? $event->short_description,
                'description' => $translated['description'] ?? $event->description,
            ],
        );
    }
}
