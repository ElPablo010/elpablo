<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            // Wie er optreedt, komma-gescheiden. Leeg = de merknaam (de DJ zelf).
            // Voedt schema.org Event.performer.
            $table->string('lineup')->nullable()->after('venue_city');
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn('lineup');
        });
    }
};
