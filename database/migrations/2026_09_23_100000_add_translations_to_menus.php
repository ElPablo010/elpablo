<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Menu's zijn één set items voor alle talen (links lokaliseren bij het
        // renderen via Locale::href()); enkel het label verschilt per taal.
        // Leeg = terugval op lang/{locale}.json en daarna op het NL-label.
        Schema::table('menu_items', function (Blueprint $table) {
            $table->string('label_en')->nullable()->after('label');
            $table->string('label_es')->nullable()->after('label_en');
        });

        Schema::table('menus', function (Blueprint $table) {
            $table->string('title_en')->nullable()->after('title');
            $table->string('title_es')->nullable()->after('title_en');
        });
    }

    public function down(): void
    {
        Schema::table('menu_items', function (Blueprint $table) {
            $table->dropColumn(['label_en', 'label_es']);
        });

        Schema::table('menus', function (Blueprint $table) {
            $table->dropColumn(['title_en', 'title_es']);
        });
    }
};
