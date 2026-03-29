<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds status_points (REAL) to fleet_ships.
 *
 * Fleet ships need fractional status tracking just like colony buildings/researches.
 * Default 20 = full health (= max_status_points standard).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fleet_ships', function (Blueprint $table) {
            $table->double('status_points')->default(20)->after('count');
        });
    }

    public function down(): void
    {
        Schema::table('fleet_ships', function (Blueprint $table) {
            $table->dropColumn('status_points');
        });
    }
};
