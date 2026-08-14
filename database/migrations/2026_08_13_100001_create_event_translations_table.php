<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Enkel EN/ES-rijen; NL leeft op de events-rij zelf. Eén event-rij =
        // gedeelde voorraad over alle talen.
        Schema::create('event_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->string('locale', 5);
            $table->string('name')->nullable();
            $table->text('short_description')->nullable();
            $table->longText('description')->nullable();
            $table->timestamps();
            $table->unique(['event_id', 'locale']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_translations');
    }
};
