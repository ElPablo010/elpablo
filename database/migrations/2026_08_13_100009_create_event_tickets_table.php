<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Eén rij = één fysiek ticket. Reserveringen (status reserved) tellen mee
        // in de capaciteit tot ze betaald of verlopen zijn.
        Schema::create('event_tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ticket_type_id')->constrained();
            $table->foreignId('ticket_order_id')->constrained()->cascadeOnDelete();
            $table->char('token', 26)->unique();      // ULID, gezet in booted()
            $table->string('status')->default('reserved'); // TicketStatus
            $table->timestamp('checked_in_at')->nullable();
            $table->foreignId('checked_in_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('pdf_path')->nullable();
            $table->timestamps();
            $table->index(['event_id', 'ticket_type_id', 'status']); // capaciteitstellingen
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_tickets');
    }
};
