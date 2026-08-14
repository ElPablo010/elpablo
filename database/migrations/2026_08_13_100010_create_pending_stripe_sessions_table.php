<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Duurzame overdracht tussen "Checkout-sessie aangemaakt" en de webhook:
        // de uuid reist als client_reference_id mee door Stripe.
        Schema::create('pending_stripe_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique();
            $table->string('type');                   // 'event_order'
            $table->json('payload');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pending_stripe_sessions');
    }
};
