<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Resultaat per event: kosten (DJ's, licht & geluid, affiches, …) en
        // opbrengsten buiten de online ticketverkoop (kassa, bar, sponsoring).
        // Bedragen zijn EXCL. btw — El Pablo BV recupereert de btw, dus die is
        // geen kost. Bewust geen categorie: omschrijving + bedrag volstaat.
        Schema::create('event_costs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->string('description');
            $table->decimal('amount', 10, 2);             // excl. btw
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
            $table->index(['event_id', 'position']);
        });

        Schema::create('event_revenues', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->string('description');
            $table->decimal('amount', 10, 2);             // excl. btw
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
            $table->index(['event_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_revenues');
        Schema::dropIfExists('event_costs');
    }
};
