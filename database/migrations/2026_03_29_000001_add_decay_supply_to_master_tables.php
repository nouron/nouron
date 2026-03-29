<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds decay_rate and supply_cost to the master data tables (buildings, ships, researches).
 *
 * - decay_rate  REAL  — fractional status points lost per tick (0.05–0.3)
 * - supply_cost INT   — permanent supply cap consumed while the entity exists
 *
 * Concrete values are seeded by MasterDataSeeder.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('buildings', function (Blueprint $table) {
            $table->double('decay_rate')->nullable()->after('max_status_points');
            $table->integer('supply_cost')->nullable()->after('decay_rate');
        });

        Schema::table('ships', function (Blueprint $table) {
            $table->double('decay_rate')->nullable()->after('max_status_points');
            $table->integer('supply_cost')->nullable()->after('decay_rate');
        });

        Schema::table('researches', function (Blueprint $table) {
            $table->double('decay_rate')->nullable()->after('max_status_points');
            $table->integer('supply_cost')->nullable()->after('decay_rate');
        });
    }

    public function down(): void
    {
        Schema::table('buildings', function (Blueprint $table) {
            $table->dropColumn(['decay_rate', 'supply_cost']);
        });

        Schema::table('ships', function (Blueprint $table) {
            $table->dropColumn(['decay_rate', 'supply_cost']);
        });

        Schema::table('researches', function (Blueprint $table) {
            $table->dropColumn(['decay_rate', 'supply_cost']);
        });
    }
};
