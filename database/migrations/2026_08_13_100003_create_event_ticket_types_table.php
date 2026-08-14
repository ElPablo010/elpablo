<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Pivot mét eigen model: de rijen worden bij checkout met lockForUpdate()
        // vergrendeld om capaciteit race-vrij te bewaken.
        Schema::create('event_ticket_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ticket_type_id')->constrained()->cascadeOnDelete();
            $table->decimal('price', 8, 2);            // incl. BTW
            $table->decimal('vat_rate', 5, 2)->default(21);
            $table->date('sales_end_date')->nullable(); // t/m deze dag te koop; null = altijd
            $table->unsignedInteger('capacity')->nullable(); // null = onbeperkt
            $table->boolean('sold_out')->default(false);     // handmatige override
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
            $table->unique(['event_id', 'ticket_type_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_ticket_types');
    }
};
