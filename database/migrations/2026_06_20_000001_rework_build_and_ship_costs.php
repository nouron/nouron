<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Reworks construction + ship costs to the resource-sink economy (PR 1, 2026-06-20).
 *
 * Buildings: Regolith (3) for all except CommandCenter (25) + Harvester (27, bootstrap),
 * plus a small Werkstoffe (4) accent on late/high-tech buildings. Mirrors the canonical
 * config/buildings.php `build_cost` — kept in sync going forward via `game:sync-config`.
 * Resets ALL resource 3 + 4 rows (incl. legacy dead building ids) and reinserts the plan.
 *
 * Ships: cost Credits only — strips the legacy Werkstoffe (4) + Organika (5) ship_costs
 * rows (owner rule: Schiffe nur Credits).
 *
 * Credits (1) / Supply (2) rows in building_costs are left untouched (legacy, not read
 * by the hex build flow). Design: GDD §3 (Verwendungsmatrix), §4 (Baukosten).
 */
return new class extends Migration
{
    /** building_id => [resource_id => amount] (3 = Regolith, 4 = Werkstoffe). */
    private array $buildCosts = [
        28 => [3 => 40],            // housingComplex
        41 => [3 => 40],            // bioFacility
        30 => [3 => 40],            // depot
        52 => [3 => 50],            // bar
        31 => [3 => 60, 4 => 20],   // sciencelab
        32 => [3 => 50, 4 => 15],   // temple
        46 => [3 => 60, 4 => 25],   // infirmary
        50 => [3 => 60, 4 => 25],   // monument
        44 => [3 => 80, 4 => 25],   // hangar
        53 => [3 => 80, 4 => 25],   // securityHub
        54 => [3 => 80],            // uplinkStation — no Werkstoffe (import gate)
        55 => [3 => 100, 4 => 25],  // tradingPost
        // CommandCenter (25) + Harvester (27): no Regolith/Werkstoffe (bootstrap).
    ];

    public function up(): void
    {
        DB::statement('PRAGMA foreign_keys = OFF');

        // Reset Regolith + Werkstoffe construction costs, then reinsert the plan.
        DB::table('building_costs')->whereIn('resource_id', [3, 4])->delete();

        $rows = [];
        foreach ($this->buildCosts as $buildingId => $costs) {
            foreach ($costs as $resourceId => $amount) {
                $rows[] = ['building_id' => $buildingId, 'resource_id' => $resourceId, 'amount' => $amount];
            }
        }
        DB::table('building_costs')->insert($rows);

        // Ships cost Credits only — drop Werkstoffe (4) + Organika (5) ship costs.
        DB::table('ship_costs')->whereIn('resource_id', [4, 5])->delete();

        DB::statement('PRAGMA foreign_keys = ON');
    }

    public function down(): void
    {
        // One-way data rebalance — Regolith/Werkstoffe build costs are removed.
        DB::table('building_costs')->whereIn('resource_id', [3, 4])->delete();
    }
};
