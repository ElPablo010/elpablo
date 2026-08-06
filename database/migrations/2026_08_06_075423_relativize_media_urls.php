<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Maakt opgeslagen media-URL's domein-onafhankelijk.
 *
 * De public-disk bouwde zijn URL's op APP_URL, waardoor het dev-domein
 * (https://elpablo.test/storage/...) in de database terechtkwam. Bij de verhuizing
 * naar el-pablo.com zou élke afbeelding en mp3 dan een 404 geven. De disk staat nu
 * op '/storage' (zie config/filesystems.php); deze migratie haalt het domein uit
 * de data die er al in zit.
 *
 * Generiek van opzet: elke host vóór /storage/ wordt gestript, dus dit werkt ook
 * als een dump van een andere omgeving wordt geïmporteerd. Idempotent — een tweede
 * keer draaien vindt simpelweg niets meer.
 *
 * Externe URL's (bv. de demo-mp3's op de oude WordPress, of Unsplash-foto's)
 * blijven ongemoeid: die bevatten geen /storage/-pad.
 */
return new class extends Migration
{
    /** Elke absolute URL die naar de eigen storage wijst → relatief pad. */
    private const PATTERN = '#https?://[^/\s"\']+/storage/#i';

    public function up(): void
    {
        $this->rewrite('website_media', ['url', 'fallback_url']);
        $this->rewrite('pages', ['seo_image_url', 'canonical_url']);
        $this->rewrite('page_sections', ['content']);
        $this->rewrite('settings', ['value']);
    }

    public function down(): void
    {
        // Niet omkeerbaar: het oorspronkelijke domein is niet te reconstrueren.
        // Ook niet nodig — relatieve URL's werken op elke omgeving.
    }

    /**
     * Herschrijf de opgegeven kolommen rij voor rij. Bewust in PHP en niet met
     * één SQL REPLACE: het domein verschilt per omgeving en JSON-kolommen moeten
     * intact blijven.
     */
    private function rewrite(string $table, array $columns): void
    {
        if (! DB::getSchemaBuilder()->hasTable($table)) {
            return;
        }

        $columns = array_values(array_filter(
            $columns,
            fn (string $column) => DB::getSchemaBuilder()->hasColumn($table, $column),
        ));

        if ($columns === []) {
            return;
        }

        DB::table($table)->orderBy('id')->chunkById(200, function ($rows) use ($table, $columns) {
            foreach ($rows as $row) {
                $changes = [];

                foreach ($columns as $column) {
                    $value = $row->{$column};

                    if (! is_string($value) || $value === '') {
                        continue;
                    }

                    $rewritten = preg_replace(self::PATTERN, '/storage/', $value);

                    if ($rewritten !== $value) {
                        $changes[$column] = $rewritten;
                    }
                }

                if ($changes !== []) {
                    DB::table($table)->where('id', $row->id)->update($changes);
                }
            }
        });
    }
};
