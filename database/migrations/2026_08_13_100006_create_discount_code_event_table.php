<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Binding van een code aan specifieke events. Geen rijen = overal geldig.
        Schema::create('discount_code_event', function (Blueprint $table) {
            $table->id();
            $table->foreignId('discount_code_id')->constrained()->cascadeOnDelete();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->unique(['discount_code_id', 'event_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('discount_code_event');
    }
};
