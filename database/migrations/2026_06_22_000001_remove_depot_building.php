<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Removes the Depot building (ID 30) — Owner decision: "ersatzlos streichen" (2026-06-22).
 *
 * Depot had no implemented game effect (no resource-cap system exists, see GDD §16
 * Befund 1) and was the only building in the active roster without a play function.
 * No other building/research/personell references it as a required_building_id, so
 * removal is a clean, isolated cut. Can be reintroduced later alongside a resource-cap
 * system if one is built.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('building_costs')->where('building_id', 30)->delete();
        DB::table('colony_buildings')->where('building_id', 30)->delete();
        DB::table('buildings')->where('id', 30)->delete();
    }

    public function down(): void
    {
        // One-way removal: Depot row, costs and colony instances are not restored
        // (destructive — use a DB snapshot if full rollback is needed).
    }
};
