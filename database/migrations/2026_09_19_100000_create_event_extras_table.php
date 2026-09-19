<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Extra's zijn géén tickets: ze staan naast de ticketselectie (een
        // gratis groepstafel, later een drankkaart) en maken dus nooit een rij
        // in event_tickets aan. Zo blijft elk ticketaantal exact het aantal
        // mensen. Voorraad, maximum per bestelling en de drempel "vanaf N
        // tickets" staan per extra ingesteld.
        Schema::create('event_extras', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->string('name');                       // NL
            $table->string('name_en')->nullable();        // leeg = terugval op NL
            $table->string('name_es')->nullable();
            $table->text('description')->nullable();
            $table->text('description_en')->nullable();
            $table->text('description_es')->nullable();
            $table->decimal('price', 8, 2)->default(0);   // incl. btw; 0 = gratis
            $table->decimal('vat_rate', 5, 2)->default(21);
            $table->unsignedInteger('capacity')->nullable();          // null = onbeperkt
            $table->unsignedSmallInteger('max_per_order')->default(1);
            $table->unsignedSmallInteger('min_tickets')->default(0);  // 0 = geen drempel
            // Leeg = tel alle tickets in de bestelling; ingevuld = tel enkel dat type.
            $table->foreignId('ticket_type_id')->nullable()->constrained()->nullOnDelete();
            $table->boolean('sold_out')->default(false);  // handmatige override
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
            $table->index(['event_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_extras');
    }
};
