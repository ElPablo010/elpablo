<?php

use App\Models\Mixtape;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Mixtapes krijgen een eigen publieke detailpagina (/mixtapes/{slug}) zodat een
 * set deelbaar is op social media en in mails. Bestaande rijen krijgen hier hun
 * slug uit de titel; nieuwe rijen via het model.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mixtapes', function (Blueprint $table): void {
            $table->string('slug')->nullable()->unique()->after('title');
        });

        foreach (Mixtape::query()->whereNull('slug')->get() as $mixtape) {
            $mixtape->update(['slug' => $this->uniqueSlug($mixtape->title)]);
        }
    }

    public function down(): void
    {
        Schema::table('mixtapes', function (Blueprint $table): void {
            $table->dropColumn('slug');
        });
    }

    private function uniqueSlug(string $title): string
    {
        $base = Str::slug($title) ?: 'mixtape';
        $slug = $base;

        for ($i = 2; Mixtape::query()->where('slug', $slug)->exists(); $i++) {
            $slug = "{$base}-{$i}";
        }

        return $slug;
    }
};
