<?php

namespace App\Console\Commands;

use App\Models\Page;
use App\Services\Translation\TranslationMediaSync;
use Illuminate\Console\Command;

/**
 * Zet de media van alle NL-pagina's door naar hun EN/ES-vertalingen. Bij het
 * opslaan van een pagina gebeurt dat vanzelf; dit commando trekt pagina's
 * gelijk die al uit elkaar liepen vóór die sync bestond.
 */
class SyncTranslationMediaCommand extends Command
{
    protected $signature = 'pages:sync-translation-media';

    protected $description = 'Neem foto\'s en video\'s van de NL-pagina\'s over in hun vertalingen';

    public function handle(TranslationMediaSync $sync): int
    {
        $pages = Page::whereNull('translation_of')->has('translations')->get();

        foreach ($pages as $page) {
            $sync->sync($page);
        }

        $this->info("Media gesynchroniseerd voor {$pages->count()} pagina('s).");

        return self::SUCCESS;
    }
}
