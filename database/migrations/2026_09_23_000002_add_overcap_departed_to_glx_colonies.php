<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('glx_colonies', function (Blueprint $table) {
            // Colonists who left because of over-capacity (GDD §6 "Überkapazität —
            // Konsequenzen", A14): unfilled workplaces. Lowers the staffing share
            // (production) and the food need; decreases automatically once housing
            // is free again (OvercapService::advanceStreaks()).
            $table->unsignedInteger('overcap_departed')->default(0)->after('overcap_streak');
        });
    }

    public function down(): void
    {
        Schema::table('glx_colonies', function (Blueprint $table) {
            $table->dropColumn('overcap_departed');
        });
    }
};
