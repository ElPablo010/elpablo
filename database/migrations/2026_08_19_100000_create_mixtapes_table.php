<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Mixtapes als eigen posttype (zoals events). Voorheen leefden de mixes als
 * repeater-items in de content-bag van de mixes-sectie — per taal een eigen
 * kopie, met mixes:sync-media als pleister om media gelijk te houden. Nu is er
 * één globale catalogus; de sectie verwijst enkel nog via show_all/mixtape_ids.
 *
 * De bestaande sectie-items worden hier meteen omgezet: de NL-secties zijn de
 * bron (dedupe op titel), elke mixes-sectie (alle talen) krijgt daarna
 * show_all óf de expliciete mixtape_ids die zijn oude items dekken.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mixtapes', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('subtitle')->nullable();
            $table->string('audio_url', 1024);
            $table->string('cover_url', 1024)->nullable();
            $table->boolean('allow_download')->default(true);
            $table->boolean('published')->default(true);
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
        });

        $this->convertSectionItems();
    }

    public function down(): void
    {
        Schema::dropIfExists('mixtapes');
    }

    private function convertSectionItems(): void
    {
        $sections = DB::table('page_sections')->where('section_type', 'mixes')->get();

        if ($sections->isEmpty()) {
            return;
        }

        $pageLocales = DB::table('pages')->pluck('locale', 'id');

        // NL eerst: die secties zijn leidend voor media, subtitel en volgorde.
        $sorted = $sections->sortBy(
            fn ($section) => ($pageLocales[$section->sectionable_id] ?? null) === 'nl' ? 0 : 1
        );

        $idByTitle = [];
        $position = 0;

        foreach ($sorted as $section) {
            $content = json_decode($section->content ?? '[]', true) ?: [];

            foreach ($content['items'] ?? [] as $item) {
                $title = trim($item['title'] ?? '');

                if ($title === '' || blank($item['audio'] ?? null) || isset($idByTitle[$title])) {
                    continue;
                }

                $idByTitle[$title] = DB::table('mixtapes')->insertGetId([
                    'title' => $title,
                    'subtitle' => $item['subtitle'] ?? null,
                    'audio_url' => $item['audio'],
                    'cover_url' => $item['cover'] ?? null,
                    'allow_download' => (bool) ($item['allow_download'] ?? true),
                    'published' => true,
                    'position' => $position++,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        $total = count($idByTitle);

        foreach ($sections as $section) {
            $content = json_decode($section->content ?? '[]', true) ?: [];

            $ids = collect($content['items'] ?? [])
                ->map(fn ($item) => $idByTitle[trim($item['title'] ?? '')] ?? null)
                ->filter()
                ->unique()
                ->values()
                ->all();

            unset($content['items']);
            $content['show_all'] = $total > 0 && count($ids) === $total;
            $content['mixtape_ids'] = $content['show_all'] ? [] : $ids;

            DB::table('page_sections')
                ->where('id', $section->id)
                ->update(['content' => json_encode($content)]);
        }
    }
};
