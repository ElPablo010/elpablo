<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Wat er per bestelling aan extra's geclaimd is. Bewust ZONDER eigen
        // status: of een claim voorraad bezet volgt uit de bestelling (betaald,
        // of nog geldige reservering). Daardoor geeft een verlopen reservering
        // of een terugbetaling de tafel vanzelf weer vrij — zonder dat er ergens
        // een tweede statusveld bijgehouden moet worden.
        Schema::create('ticket_order_extras', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('event_extra_id')->nullable()->constrained()->nullOnDelete();
            $table->string('description');             // naam-snapshot bij aankoop
            $table->unsignedSmallInteger('quantity');
            $table->decimal('unit_price_inc_vat', 10, 4);
            $table->decimal('vat_rate', 5, 2);         // per lijn, zoals bij tickets
            $table->decimal('line_total_inc_vat', 8, 2);
            $table->timestamps();
            $table->index('event_extra_id');           // voorraadtellingen
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_order_extras');
    }
};
