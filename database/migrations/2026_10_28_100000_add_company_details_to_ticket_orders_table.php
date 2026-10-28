<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ticket_orders', function (Blueprint $table) {
            $table->string('company_name', 190)->nullable()->after('buyer_email');
            $table->string('company_vat', 30)->nullable()->after('company_name');
            $table->text('company_address')->nullable()->after('company_vat');
        });
    }

    public function down(): void
    {
        Schema::table('ticket_orders', function (Blueprint $table) {
            $table->dropColumn(['company_name', 'company_vat', 'company_address']);
        });
    }
};
