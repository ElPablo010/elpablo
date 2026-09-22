<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('event_ticket_types', function (Blueprint $table) {
            // Tegenhanger van sales_end_date: vanaf deze dag te koop; null = meteen.
            // Voedt ook schema.org offers.validFrom (Search Console vroeg erom).
            $table->date('sales_start_date')->nullable()->after('vat_rate');
        });
    }

    public function down(): void
    {
        Schema::table('event_ticket_types', function (Blueprint $table) {
            $table->dropColumn('sales_start_date');
        });
    }
};
