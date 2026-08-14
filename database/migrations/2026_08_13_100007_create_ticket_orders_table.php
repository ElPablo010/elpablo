<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Gast-bestellingen: er zijn geen klantaccounts, de koper is naam + e-mail.
        Schema::create('ticket_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->string('buyer_name');
            $table->string('buyer_email')->index();
            $table->string('locale', 5)->default('nl'); // taal van bevestigingsmail/tickets
            $table->string('status')->default('pending')->index(); // OrderStatus
            $table->decimal('subtotal_inc_vat', 8, 2);  // vóór kortingscode
            $table->decimal('total_inc_vat', 8, 2);     // ná kortingscode
            $table->foreignId('discount_code_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('discount_amount', 8, 2)->nullable();
            $table->string('stripe_session_id')->nullable()->unique(); // idempotentie-anker
            $table->string('stripe_payment_intent_id')->nullable();    // voor refunds
            $table->timestamp('expires_at')->nullable()->index(); // reserveringsdeadline
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('refunded_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_orders');
    }
};
