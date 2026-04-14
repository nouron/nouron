<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 3a: Reduce ships and buildings to the Mini-4X core set (GDD §4, §6).
 *
 * Buildings: 25 → 12. Removes 13 civil/military buildings that don't fit the
 * reduced scope (stadium, casino, prison, secretOps, militarySpaceyard, etc.).
 *
 * Ships: 6 → 3.
 *   - Renamed: fighter1 (37) → ship_corvette, smallTransporter (47) → ship_freighter
 *   - New:     ship_sonde (85) — unmanned, supply_cost=0
 *   - Removed: frigate1 (29), battlecruiser1 (49), mediumTransporter (83), largeTransporter (84)
 */
return new class extends Migration
{
    /** IDs of buildings removed in Phase 3a. */
    private const BUILDINGS_TO_REMOVE = [42, 45, 48, 51, 53, 54, 55, 56, 64, 65, 66, 68, 70];

    /** IDs of ships removed in Phase 3a. */
    private const SHIPS_TO_REMOVE = [29, 49, 83, 84];

    public function up(): void
    {
        // ── Buildings ─────────────────────────────────────────────────────────

        DB::table('building_costs')->whereIn('building_id', self::BUILDINGS_TO_REMOVE)->delete();
        DB::table('colony_buildings')->whereIn('building_id', self::BUILDINGS_TO_REMOVE)->delete();
        DB::table('buildings')->whereIn('id', self::BUILDINGS_TO_REMOVE)->delete();

        // ── Ships (remove) ────────────────────────────────────────────────────

        DB::table('ship_costs')->whereIn('ship_id', self::SHIPS_TO_REMOVE)->delete();
        DB::table('colony_ships')->whereIn('ship_id', self::SHIPS_TO_REMOVE)->delete();
        DB::table('fleet_ships')->whereIn('ship_id', self::SHIPS_TO_REMOVE)->delete();
        DB::table('ships')->whereIn('id', self::SHIPS_TO_REMOVE)->delete();

        // ── Ships (rename) ────────────────────────────────────────────────────

        DB::table('ships')->where('id', 37)->update(['name' => 'ship_corvette', 'supply_cost' => 14, 'moving_speed' => 4]);
        DB::table('ships')->where('id', 47)->update(['name' => 'ship_freighter', 'supply_cost' => 6,  'moving_speed' => 3]);

        // ── Ships (insert sonde) ──────────────────────────────────────────────

        if (!DB::table('ships')->where('id', 85)->exists()) {
            DB::table('ships')->insert([
                'id'                     => 85,
                'purpose'                => 'military',
                'name'                   => 'ship_sonde',
                'required_building_id'   => 44,   // hangar
                'required_building_level'=> 1,
                'prime_colony_only'      => 0,
                'row'                    => 9,
                'column'                 => 5,
                'ap_for_levelup'         => 5,
                'max_status_points'      => 20,
                'moving_speed'           => 5,
                'decay_rate'             => 0.10,  // ticks_until_lost ~200
                'supply_cost'            => 0,
            ]);
        }
    }

    public function down(): void
    {
        // Remove sonde
        DB::table('ship_costs')->where('ship_id', 85)->delete();
        DB::table('colony_ships')->where('ship_id', 85)->delete();
        DB::table('fleet_ships')->where('ship_id', 85)->delete();
        DB::table('ships')->where('id', 85)->delete();

        // Revert ship renames
        DB::table('ships')->where('id', 37)->update(['name' => 'techs_fighter1']);
        DB::table('ships')->where('id', 47)->update(['name' => 'techs_smallTransporter']);

        // Re-insert removed ships (name only — decay/supply set by MasterDataSeeder)
        $removed = [
            ['id' => 29, 'purpose' => 'military', 'name' => 'techs_frigate1',         'row' => 10, 'column' => 5, 'ap_for_levelup' => 15],
            ['id' => 49, 'purpose' => 'military', 'name' => 'techs_battlecruiser1',    'row' => 12, 'column' => 5, 'ap_for_levelup' => 15],
            ['id' => 83, 'purpose' => 'economy',  'name' => 'techs_mediumTransporter', 'row' =>  9, 'column' => 4, 'ap_for_levelup' => 15],
            ['id' => 84, 'purpose' => 'economy',  'name' => 'techs_largeTransporter',  'row' => 11, 'column' => 4, 'ap_for_levelup' => 15],
        ];
        foreach ($removed as $ship) {
            if (!DB::table('ships')->where('id', $ship['id'])->exists()) {
                DB::table('ships')->insert($ship + ['prime_colony_only' => 0]);
            }
        }

        // Note: removed buildings are not restored (data loss accepted for down()).
    }
};
