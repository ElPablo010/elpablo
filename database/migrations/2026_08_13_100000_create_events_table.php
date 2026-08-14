<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();         // gedeeld over alle locales
            $table->string('name');                   // NL-bron; EN/ES in event_translations
            $table->text('short_description')->nullable();
            $table->longText('description')->nullable();
            $table->date('start_date')->index();
            $table->date('end_date')->nullable();
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->string('venue_name')->nullable();
            $table->string('venue_address')->nullable();
            $table->string('venue_city')->nullable();
            $table->string('image_url')->nullable();  // URL-string, huisstijl (MediaPicker)
            $table->string('image_alt')->nullable();
            $table->boolean('published')->default(false);
            $table->timestamp('cancelled_at')->nullable(); // afgelast, maar blijft zichtbaar
            $table->text('cancellation_message')->nullable();
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
