<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Removes the colonyShip (id=88) from all tables.
 *
 * Colonization is not part of the game concept — players have exactly one colony.
 * Outposts are a Phase 3 consideration and will not use colony ships.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Delete child-table rows first to respect FK constraints, then the parent.
        DB::table('fleet_ships')->where('ship_id', 88)->delete();
        DB::table('colony_ships')->where('ship_id', 88)->delete();
        DB::table('ship_costs')->where('ship_id', 88)->delete();
        DB::table('ships')->where('id', 88)->delete();
    }

    public function down(): void
    {
        // Not reversible — colonyShip is intentionally removed from the game.
    }
};
