<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Globale catalogus ("Standaard", "VIP", …). De echte prijs/capaciteit
        // per event leeft op de event_ticket_types-pivot.
        Schema::create('ticket_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');                    // NL
            $table->string('name_en')->nullable();     // fallback = name
            $table->string('name_es')->nullable();
            $table->decimal('default_price', 8, 2)->nullable();  // incl. BTW
            $table->decimal('default_vat_rate', 5, 2)->default(21);
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_types');
    }
};
