<?php

namespace Tests\Feature\GameTick;

use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Tests for the game:tick Artisan command.
 *
 * Each test sets up a specific DB state, runs the tick for a unique high tick number
 * (to avoid touching fleet_orders), and asserts the result.
 *
 * Test data (from TestSeeder / testdata.sqlite.sql):
 *   Colony 1 (Springfield), user_id=3 (Bart)
 *     CC (building 25):      level=10, status_points=16
 *     oremine (building 27): level=5,  status_points=11
 *     housing (building 28): level=2,  status_points=10
 *   Colony 2 (Shelbyville), user_id=0 (no player)
 *   Fleet 8 (user 3): has korvette (ship_id=37, count=5)
 *   user_resources: user 3 → supply=1938 (will be overwritten by cap model)
 */
class GameTickTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(TestSeeder::class)->run();
    }

    // ── Supply cap ───────────────────────────────────────────────────────────

    /**
     * Supply cap = CC_level × 10 + housing_units × 8.
     * Colony 1: CC level=5 (max), housing level=2 → cap = 50 + 16 = 66.
     */
    public function test_supply_cap_is_set_from_cc_and_housing(): void
    {
        Artisan::call('game:tick', ['--tick' => 9001]);

        $supply = DB::table('user_resources')->where('user_id', 3)->value('supply');
        $this->assertEquals(66, $supply);
    }

    /**
     * When CC is missing (level=0), supply cap must be 0.
     */
    public function test_supply_is_zero_without_commandcenter(): void
    {
        DB::table('colony_buildings')
            ->where('colony_id', 1)->where('building_id', 25)
            ->update(['level' => 0]);

        Artisan::call('game:tick', ['--tick' => 9002]);

        $supply = DB::table('user_resources')->where('user_id', 3)->value('supply');
        $this->assertEquals(0, $supply);
    }

    /**
     * Supply is capped at cap_max (200) regardless of housing level.
     */
    public function test_supply_cap_respects_maximum(): void
    {
        // housing level 30: 15 + 30*8 = 255 → capped at 200
        DB::table('colony_buildings')
            ->where('colony_id', 1)->where('building_id', 28)
            ->update(['level' => 30]);

        Artisan::call('game:tick', ['--tick' => 9003]);

        $supply = DB::table('user_resources')->where('user_id', 3)->value('supply');
        $this->assertEquals(200, $supply);
    }

    // ── Building decay ───────────────────────────────────────────────────────

    /**
     * Building status_points decreases by decay_rate each tick.
     * oremine (id 27): decay_rate=0.17; starting SP=11 → 11 - 0.17 = 10.83
     *
     * Supply costs are zeroed so colony 1 is never over-cap (overcap would double the rate).
     */
    public function test_building_status_points_decrease_by_decay_rate(): void
    {
        // Zero all supply costs so free-supply is always >= 0 and no overcap multiplier fires.
        DB::table('buildings')->update(['supply_cost' => 0]);
        DB::table('knowledge')->update(['supply_cost' => 0]);
        DB::table('ships')->update(['supply_cost' => 0]);

        DB::table('colony_buildings')
            ->where('colony_id', 1)->where('building_id', 27)
            ->update(['status_points' => 11.0, 'level' => 5]);

        Artisan::call('game:tick', ['--tick' => 9010]);

        $sp = (float) DB::table('colony_buildings')
            ->where('colony_id', 1)->where('building_id', 27)
            ->value('status_points');

        $this->assertEqualsWithDelta(11.0 - 0.17, $sp, 0.001);
    }

    /**
     * When status_points hits ≤ 0, the building loses one level and SP resets to max.
     */
    public function test_building_levels_down_when_status_points_depleted(): void
    {
        DB::table('colony_buildings')
            ->where('colony_id', 1)->where('building_id', 27)
            ->update(['status_points' => 0.1, 'level' => 5]);

        Artisan::call('game:tick', ['--tick' => 9011]);

        $row = DB::table('colony_buildings')
            ->where('colony_id', 1)->where('building_id', 27)
            ->first();

        $this->assertEquals(4, $row->level);
        $this->assertEquals(20, (int) $row->status_points); // reset to max_status_points
    }

    /**
     * Buildings at level 0 are skipped by decay (nothing to decay).
     */
    public function test_building_at_level_zero_is_not_decayed(): void
    {
        DB::table('colony_buildings')
            ->where('colony_id', 1)->where('building_id', 27)
            ->update(['status_points' => 5.0, 'level' => 0]);

        Artisan::call('game:tick', ['--tick' => 9012]);

        $row = DB::table('colony_buildings')
            ->where('colony_id', 1)->where('building_id', 27)
            ->first();

        $this->assertEquals(0, $row->level);
        $this->assertEqualsWithDelta(5.0, (float) $row->status_points, 0.001); // unchanged
    }

    // ── Ship decay ───────────────────────────────────────────────────────────

    /**
     * Fleet ship status_points decreases by ship decay_rate each tick.
     * korvette (id 37): decay_rate=0.15; starting SP=20 → 19.85
     */
    public function test_ship_status_points_decrease_by_decay_rate(): void
    {
        DB::table('fleet_ships')
            ->where('fleet_id', 8)->where('ship_id', 37)
            ->update(['status_points' => 20.0]);

        Artisan::call('game:tick', ['--tick' => 9020]);

        $sp = (float) DB::table('fleet_ships')
            ->where('fleet_id', 8)->where('ship_id', 37)
            ->value('status_points');

        $this->assertEqualsWithDelta(20.0 - 0.15, $sp, 0.001);
    }

    /**
     * When fleet ship status_points hits ≤ 0, the fleet_ships entry is removed.
     */
    public function test_ship_destroyed_when_status_points_depleted(): void
    {
        DB::table('fleet_ships')
            ->where('fleet_id', 8)->where('ship_id', 37)
            ->update(['status_points' => 0.1]);

        Artisan::call('game:tick', ['--tick' => 9021]);

        $exists = DB::table('fleet_ships')
            ->where('fleet_id', 8)->where('ship_id', 37)
            ->exists();

        $this->assertFalse($exists);
    }

    // ── Research decay ───────────────────────────────────────────────────────

    /**
     * Research status_points decreases by decay_rate each tick.
     * construction (id 90): decay_rate=0.13; level=2, SP=20 → 19.87
     *
     * Supply costs are zeroed so colony 1 is never over-cap (overcap would double the rate).
     */
    public function test_research_status_points_decrease_by_decay_rate(): void
    {
        // Zero all supply costs so free-supply is always >= 0 and no overcap multiplier fires.
        DB::table('buildings')->update(['supply_cost' => 0]);
        DB::table('knowledge')->update(['supply_cost' => 0]);
        DB::table('ships')->update(['supply_cost' => 0]);

        DB::table('colony_knowledge')
            ->where('colony_id', 1)->where('research_id', 90)
            ->update(['status_points' => 20.0, 'level' => 2]);

        Artisan::call('game:tick', ['--tick' => 9030]);

        $sp = (float) DB::table('colony_knowledge')
            ->where('colony_id', 1)->where('research_id', 90)
            ->value('status_points');

        $this->assertEqualsWithDelta(20.0 - 0.13, $sp, 0.001);
    }

    /**
     * When research status_points hits ≤ 0, it loses one level and SP resets to max.
     */
    public function test_research_levels_down_when_status_points_depleted(): void
    {
        DB::table('colony_knowledge')
            ->where('colony_id', 1)->where('research_id', 90)
            ->update(['status_points' => 0.1, 'level' => 2]);

        Artisan::call('game:tick', ['--tick' => 9031]);

        $row = DB::table('colony_knowledge')
            ->where('colony_id', 1)->where('research_id', 90)
            ->first();

        $this->assertEquals(1, $row->level);
        $this->assertEquals(20, (int) $row->status_points);
    }
}
