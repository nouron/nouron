<?php

namespace Tests\Feature\GameTick;

use App\Events\SolAdvanced;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

/**
 * Tests for the game:tick Artisan command.
 *
 * Each test sets up a specific DB state, runs the tick for a unique high tick number,
 * and asserts the result.
 *
 * Test data (from TestSeeder / testdata.sqlite.sql):
 *   Colony 1 (Springfield), user_id=3 (Bart)
 *     CC (building 25):      level=10, status_points=16
 *     oremine (building 27): level=5,  status_points=11
 *     housing (building 28): level=2,  status_points=10
 *   Colony 2 (Shelbyville), user_id=0 (no player)
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
     * Supply cap = CC_flat (10) + housing_level * 8.
     * Colony 1: CC level=3 (>0 → flat 10), housing level=2 → cap = 10 + 16 = 26.
     */
    public function test_supply_cap_is_set_from_cc_and_housing(): void
    {
        Artisan::call('game:tick', ['--tick' => 9001]);

        $supply = DB::table('user_resources')->where('user_id', 3)->value('supply');
        $this->assertEquals(26, $supply);
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
     * Supply cap sums all housing instance levels (instanced building, multiple rows per colony).
     *
     * Colony 1 baseline: CC level=3 → flat 10. One existing housing row at level 2 → 16.
     * Add two more housing instances at level 2 each → total housing sum = 6 → cap = 10 + (6×8) = 58.
     */
    public function test_supply_cap_sums_all_housing_instances(): void
    {
        // Insert two additional housing instances for colony 1 (building_id=28 is instanced).
        DB::table('colony_buildings')->insert([
            ['colony_id' => 1, 'building_id' => 28, 'level' => 2, 'status_points' => 20, 'ap_spend' => 0, 'instance_id' => 2],
            ['colony_id' => 1, 'building_id' => 28, 'level' => 2, 'status_points' => 20, 'ap_spend' => 0, 'instance_id' => 3],
        ]);

        Artisan::call('game:tick', ['--tick' => 9004]);

        $supply = DB::table('user_resources')->where('user_id', 3)->value('supply');

        // Total housing level sum = 2 + 2 + 2 = 6; cap_housingcomplex = 8; cap_commandcenter = 10
        // cap = 10 + (6 × 8) = 58
        $this->assertEquals(58, $supply);
    }

    /**
     * Supply is capped at cap_max (200) regardless of housing level.
     */
    public function test_supply_cap_respects_maximum(): void
    {
        // housing level 30: 10 + 30*8 = 250 → capped at 200
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
        DB::table('researches')->update(['supply_cost' => 0]);
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

    // ── Domain events (ADR 0003) ─────────────────────────────────────────────

    /**
     * A regularly completed tick fires SolAdvanced with the processed run and tick.
     */
    public function test_sol_advanced_event_fires_on_regular_tick(): void
    {
        Event::fake([SolAdvanced::class]);

        Artisan::call('game:tick', ['--run' => 1, '--tick' => 9020]);

        Event::assertDispatched(SolAdvanced::class, function (SolAdvanced $event) {
            return $event->run->id === 1 && $event->tick === 9020;
        });
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

    // ── Research decay ───────────────────────────────────────────────────────

    /**
     * Research status_points decreases by decay_rate each tick.
     * test_decay_placeholder (id 9901): decay_rate=0.13; level=2, SP=20 → 19.87
     *
     * Supply costs are zeroed so colony 1 is never over-cap (overcap would double the rate).
     */
    public function test_research_status_points_decrease_by_decay_rate(): void
    {
        // Zero all supply costs so free-supply is always >= 0 and no overcap multiplier fires.
        DB::table('buildings')->update(['supply_cost' => 0]);
        DB::table('researches')->update(['supply_cost' => 0]);
        DB::table('ships')->update(['supply_cost' => 0]);

        DB::table('colony_researches')
            ->where('colony_id', 1)->where('research_id', 9901)
            ->update(['status_points' => 20.0, 'level' => 2]);

        Artisan::call('game:tick', ['--tick' => 9030]);

        $sp = (float) DB::table('colony_researches')
            ->where('colony_id', 1)->where('research_id', 9901)
            ->value('status_points');

        $this->assertEqualsWithDelta(20.0 - 0.13, $sp, 0.001);
    }

    /**
     * When research status_points hits ≤ 0, it loses one level and SP resets to max.
     */
    public function test_research_levels_down_when_status_points_depleted(): void
    {
        DB::table('colony_researches')
            ->where('colony_id', 1)->where('research_id', 9901)
            ->update(['status_points' => 0.1, 'level' => 2]);

        Artisan::call('game:tick', ['--tick' => 9031]);

        $row = DB::table('colony_researches')
            ->where('colony_id', 1)->where('research_id', 9901)
            ->first();

        $this->assertEquals(1, $row->level);
        $this->assertEquals(20, (int) $row->status_points);
    }
}
