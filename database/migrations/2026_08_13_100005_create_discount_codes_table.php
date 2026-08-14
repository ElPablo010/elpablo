<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('discount_codes', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();         // uppercased via mutator
            $table->string('description')->nullable();
            $table->string('type');                   // DiscountCodeType
            $table->decimal('value', 8, 2);           // % of € afhankelijk van type
            $table->boolean('per_ticket')->default(false); // vast bedrag × aantal tickets
            $table->date('valid_from')->nullable();
            $table->date('valid_until')->nullable();
            $table->unsignedInteger('max_uses')->nullable();          // null = onbeperkt
            $table->unsignedInteger('max_uses_per_email')->nullable(); // gasten: per e-mail
            $table->decimal('min_order_amount', 8, 2)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('discount_codes');
    }
};
