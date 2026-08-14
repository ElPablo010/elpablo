<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Automatische promo's per event + tickettype: early-bird vaste prijs of
        // "koop X + Y gratis". Geen code nodig; het datumvenster is verplicht.
        Schema::create('event_ticket_discounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ticket_type_id')->constrained()->cascadeOnDelete();
            $table->string('name');                   // "Early bird", …
            $table->string('type');                   // TicketDiscountType
            $table->decimal('price', 8, 2)->nullable();          // bij fixed_price
            $table->unsignedSmallInteger('buy_quantity')->nullable();  // bij buy_x_get_y
            $table->unsignedSmallInteger('free_quantity')->nullable();
            $table->date('valid_from');
            $table->date('valid_until');
            $table->timestamps();
            $table->index(['event_id', 'ticket_type_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_ticket_discounts');
    }
};
