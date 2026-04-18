<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Updates the ships catalogue and their resource costs.
 *
 * Changes:
 * - Renames ship keys to the new internal naming convention (ship_* prefix)
 *   techs_fighter1       (37) → ship_korvette
 *   techs_smallTransporter (47) → ship_frachter
 * - Inserts the new Sonde unit (ID 85, military scout, hangar required)
 * - Clears ship_costs for deprecated ship IDs (29, 49, 83, 84, 88)
 * - Replaces ship_costs for active ships (37, 47, 85) with calibrated values
 *   covering Credits (1), Werkstoffe (4), Organika (5)
 *
 * Design context: GDD §5 (Schiffe), §11 (Balancing)
 * Internal key naming convention: see memory feedback_internal_key_naming.md
 */
return new class extends Migration
{
    /** @var array<int, array{resource_id: int, amount: int}[]> */
    private array $newCosts = [
        85 => [
            ['resource_id' => 1, 'amount' =>  500],
            ['resource_id' => 4, 'amount' =>    5],
            ['resource_id' => 5, 'amount' =>    2],
        ],
        47 => [
            ['resource_id' => 1, 'amount' => 2000],
            ['resource_id' => 4, 'amount' =>   20],
            ['resource_id' => 5, 'amount' =>   10],
        ],
        37 => [
            ['resource_id' => 1, 'amount' => 5000],
            ['resource_id' => 4, 'amount' =>   50],
            ['resource_id' => 5, 'amount' =>   30],
        ],
    ];

    /** Ship IDs that have been removed from the game. */
    private array $deprecatedShipIds = [29, 49, 83, 84, 88];

    public function up(): void
    {
        DB::statement('PRAGMA foreign_keys = OFF');

        // 1. Rename active ship keys to the new naming convention.
        DB::table('ships')->where('id', 37)->update(['name' => 'ship_korvette']);
        DB::table('ships')->where('id', 47)->update(['name' => 'ship_frachter']);

        // 2. Insert Sonde (ID 85) — skip silently if it already exists.
        DB::table('ships')->insertOrIgnore([
            'id'                      => 85,
            'purpose'                 => 'military',
            'name'                    => 'ship_sonde',
            'required_building_id'    => 44,   // hangar (civilianSpaceyard)
            'required_building_level' => 1,
            'required_research_id'    => null,
            'required_research_level' => null,
            'prime_colony_only'       => 0,
            'row'                     => 6,
            'column'                  => 6,
            'ap_for_levelup'          => 3,
            'max_status_points'       => 10,
            'moving_speed'            => 5,
        ]);

        // 3. Remove costs for deprecated ships.
        DB::table('ship_costs')
            ->whereIn('ship_id', $this->deprecatedShipIds)
            ->delete();

        // 4. Replace costs for active ships with calibrated values.
        DB::table('ship_costs')
            ->whereIn('ship_id', array_keys($this->newCosts))
            ->delete();

        $rows = [];
        foreach ($this->newCosts as $shipId => $costs) {
            foreach ($costs as $cost) {
                $rows[] = array_merge(['ship_id' => $shipId], $cost);
            }
        }
        DB::table('ship_costs')->insert($rows);

        DB::statement('PRAGMA foreign_keys = ON');
    }

    public function down(): void
    {
        // Remove Sonde.
        DB::table('ships')->where('id', 85)->delete();
        DB::table('ship_costs')->where('ship_id', 85)->delete();

        // Revert name changes.
        DB::table('ships')->where('id', 37)->update(['name' => 'techs_fighter1']);
        DB::table('ships')->where('id', 47)->update(['name' => 'techs_smallTransporter']);

        // Restore original (Credits-only) costs for active ships.
        DB::table('ship_costs')
            ->whereIn('ship_id', [37, 47])
            ->delete();

        DB::table('ship_costs')->insert([
            ['ship_id' => 37, 'resource_id' => 1, 'amount' => 10000],
            ['ship_id' => 47, 'resource_id' => 1, 'amount' => 10000],
        ]);

        // Restore original costs for deprecated ships.
        // NOTE: deprecated ship rows in `ships` are NOT restored here — this
        // migration only manages costs. If those ships need to be re-inserted,
        // a dedicated rollback migration is required.
        DB::table('ship_costs')->insert([
            ['ship_id' => 29, 'resource_id' => 1, 'amount' => 10000],
            ['ship_id' => 49, 'resource_id' => 1, 'amount' => 10000],
            ['ship_id' => 83, 'resource_id' => 1, 'amount' => 10000],
            ['ship_id' => 84, 'resource_id' => 1, 'amount' => 10000],
        ]);
    }
};
