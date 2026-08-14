<?php

namespace App\Services\Translation;

use App\Models\Event;
use App\Models\Page;
use Illuminate\Database\Eloquent\Model;

/**
 * Eén ingang om "dit record, die taal" te vertalen, welk type het ook is.
 * Zowel de synchrone rij-knop als de queued bulk-job komen hier langs, zodat
 * de koppeling model → vertaler op precies één plek staat.
 */
class TranslateRecord
{
    public function handle(Model $record, string $locale): void
    {
        match (true) {
            $record instanceof Page => app(PageTranslator::class)->translate($record, $locale),
            $record instanceof Event => app(EventTranslator::class)->translate($record, $locale),
            default => throw new TranslationException('Dit type record kan niet vertaald worden.'),
        };
    }
}
