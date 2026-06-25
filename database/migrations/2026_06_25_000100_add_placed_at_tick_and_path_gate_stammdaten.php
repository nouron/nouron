<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Three changes bundled for the Sol-3 path-choice feature:
 *
 * 1. colony_buildings.placed_at_tick — records the tick when a building was
 *    placed; used by path-gate logic to determine which Sol a path building
 *    was chosen.
 *
 * 2. Hangar (44) stammdaten: lower CC-gate from Lv3 → Lv2 (one of three
 *    parallel "path" buildings at CC Lv2) and correct supply_cost to 6
 *    (ships carry supply cost themselves; hangar should be cheap to place).
 *
 * 3. CommandCenter (25) stammdaten: require bioFacility (41) at Lv1 before
 *    CC can be upgraded. Enforces Organika flow on the Sol-1/2 ramp.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('colony_buildings', function (Blueprint $table) {
            $table->integer('placed_at_tick')->nullable()->after('pending_until_tick');
        });

        // Hangar (44): lower CC-level gate from 3 → 2; correct supply_cost 20 → 6
        DB::table('buildings')->where('id', 44)->update([
            'required_building_level' => 2,
            'supply_cost' => 6,
        ]);

        // CommandCenter (25): require bioFacility (41) at Lv1 before upgrade
        DB::table('buildings')->where('id', 25)->update([
            'required_building_id' => 41,
            'required_building_level' => 1,
        ]);
    }

    public function down(): void
    {
        Schema::table('colony_buildings', function (Blueprint $table) {
            $table->dropColumn('placed_at_tick');
        });

        // Revert Hangar (44) stammdaten
        DB::table('buildings')->where('id', 44)->update([
            'required_building_level' => 3,
            'supply_cost' => 20,
        ]);

        // Revert CommandCenter (25) stammdaten
        DB::table('buildings')->where('id', 25)->update([
            'required_building_id' => null,
            'required_building_level' => null,
        ]);
    }
};
