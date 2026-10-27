<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * T43-H9: the T30 self-salvage progress columns were never read; finds are recovered
 * instantly or by salvage flight.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('colony_tiles', function (Blueprint $table) {
            $table->dropColumn(['salvage_ap_spent', 'salvage_tick']);
        });
    }

    public function down(): void
    {
        Schema::table('colony_tiles', function (Blueprint $table) {
            $table->integer('salvage_ap_spent')->default(0);
            $table->integer('salvage_tick')->nullable();
        });
    }
};
