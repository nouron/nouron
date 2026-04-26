<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Reduces the buildings table from 24 entries to the 11 canonical buildings
 * defined in config/buildings.php (new Singleplayer Roguelike design, GDD §4).
 *
 * Buildings kept (IDs): 25, 27, 28, 30, 31, 32, 41, 44, 46, 50, 52
 * Buildings removed (13): 42, 45, 48, 51, 53, 54, 55, 56, 64, 65, 66, 68, 70
 *
 * All remaining buildings.name keys are renamed from techs_* to building_*
 * to align with the new naming convention (GDD §4, feedback: internal keys
 * must match the concept they represent).
 *
 * Renamed name keys:
 *   25  techs_commandCenter     → building_commandCenter
 *   27  techs_oremine           → building_harvester     (harvests Regolith on tiles)
 *   28  techs_housingComplex    → building_housingComplex
 *   30  techs_depot             → building_depot
 *   31  techs_sciencelab        → building_sciencelab
 *   32  techs_temple            → building_temple
 *   41  techs_silicatemine      → building_bioFacility   (produces Organika)
 *   44  techs_civilianSpaceyard → building_hangar        (unified ship hangar)
 *   46  techs_hospital          → building_hospital
 *   50  techs_denkmal           → building_denkmal
 *   52  techs_bar               → building_bar
 *
 * CC max_level corrected: 10 → 5 (new design caps CC at Lv5).
 */
return new class extends Migration
{
    private const REMOVE = [42, 45, 48, 51, 53, 54, 55, 56, 64, 65, 66, 68, 70];

    private const RENAME = [
        25 => 'building_commandCenter',
        27 => 'building_harvester',
        28 => 'building_housingComplex',
        30 => 'building_depot',
        31 => 'building_sciencelab',
        32 => 'building_temple',
        41 => 'building_bioFacility',
        44 => 'building_hangar',
        46 => 'building_hospital',
        50 => 'building_denkmal',
        52 => 'building_bar',
    ];

    public function up(): void
    {
        // Clear FK references in all dependent tables BEFORE deleting master rows
        DB::table('building_costs')
            ->whereIn('building_id', self::REMOVE)
            ->delete();

        DB::table('colony_buildings')
            ->whereIn('building_id', self::REMOVE)
            ->delete();

        // Self-referencing FK in buildings (required_building_id)
        DB::table('buildings')
            ->whereIn('required_building_id', self::REMOVE)
            ->update(['required_building_id' => null, 'required_building_level' => null]);

        // FK references in other master tables
        DB::table('researches')
            ->whereIn('required_building_id', self::REMOVE)
            ->update(['required_building_id' => null, 'required_building_level' => null]);

        DB::table('ships')
            ->whereIn('required_building_id', self::REMOVE)
            ->update(['required_building_id' => null, 'required_building_level' => null]);

        DB::table('personell')
            ->whereIn('required_building_id', self::REMOVE)
            ->update(['required_building_id' => null, 'required_building_level' => null]);

        // Now safe to delete the master rows
        DB::table('buildings')
            ->whereIn('id', self::REMOVE)
            ->delete();

        // Rename buildings.name keys for remaining buildings
        foreach (self::RENAME as $id => $newKey) {
            DB::table('buildings')->where('id', $id)->update(['name' => $newKey]);
        }

        // Update prerequisites for remaining buildings to CC-based requirements (GDD §4)
        DB::table('buildings')->where('id', 25)->update(['max_level' => 5]);
        DB::table('buildings')->where('id', 27)->update(['required_building_id' => 25, 'required_building_level' => 1, 'max_level' => 1]);
        DB::table('buildings')->where('id', 28)->update(['required_building_id' => 25, 'required_building_level' => 1]);
        DB::table('buildings')->where('id', 30)->update(['required_building_id' => 25, 'required_building_level' => 1]);
        DB::table('buildings')->where('id', 32)->update(['required_building_id' => null, 'required_building_level' => null]);
        DB::table('buildings')->where('id', 41)->update(['required_building_id' => 25, 'required_building_level' => 1]);
        DB::table('buildings')->where('id', 44)->update(['required_building_id' => 25, 'required_building_level' => 2]);
        DB::table('buildings')->where('id', 46)->update(['required_building_id' => 25, 'required_building_level' => 2]);
        DB::table('buildings')->where('id', 52)->update(['required_building_id' => 25, 'required_building_level' => 1]);
    }

    public function down(): void
    {
        // Reverting deleted buildings is not supported — data would need to be re-seeded
        // from testdata.sqlite.sql. Restore by running: php artisan db:reset
    }
};
