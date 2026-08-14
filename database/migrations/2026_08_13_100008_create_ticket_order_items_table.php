<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ticket_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ticket_type_id')->constrained();
            $table->string('description');            // naam-snapshot op moment van aankoop
            $table->unsignedSmallInteger('quantity');
            $table->unsignedSmallInteger('free_quantity')->default(0); // BOGO-gratis
            // Regeltotaal gelijkmatig gespreid (6 dec.) zodat stuksprijs × aantal exact blijft.
            $table->decimal('unit_price_inc_vat', 10, 4);
            $table->decimal('vat_rate', 5, 2);        // per lijn — nooit op de header hardcoden
            $table->decimal('line_total_inc_vat', 8, 2);
            $table->string('discount_name')->nullable(); // promo-snapshot
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_order_items');
    }
};
