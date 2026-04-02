<?php

namespace Tests\Feature\GameTick;

use App\Services\ResourcesService;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Supply over-cap penalty tests.
 *
 * Rule (GDD §6): When a colony's owner has consumed more supply than their cap
 * (getFreeSupply() < 0), buildings and researches decay at 2× the normal rate.
 * Ships are fleet-scoped and are not affected.
 *
 * Over-cap setup used throughout:
 *   1. Zero all supply_cost on buildings, researches, ships (clean slate).
 *   2. Set oremine (building_id=27) supply_cost=2, colony 1 oremine level=5 → used=10.
 *   3. Set user_resources.supply=0 for user 3 → cap=0, free=-10.
 *
 * Within-cap setup:
 *   Zero all supply_cost — used=0, so any cap >= 0 means free >= 0.
 *
 * Test data (TestSeeder):
 *   Colony 1 (Springfield) user_id=3
 *     oremine  (building_id=27): decay_rate=0.17, supply_cost=2 (after over-cap setup)
 *     biology  (research_id=33): decay_rate=0.13
 *   Colony 2 (Shelbyville) user_id=0 — no user_resources row → cap=0
 */
class OverCapDecayTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(TestSeeder::class)->run();
    }

    /**
     * Zero all supply costs on all master tables (clean slate helper).
     * Individual tests then set the costs they need.
     */
    private function zeroAllSupplyCosts(): void
    {
        DB::table('buildings')->update(['supply_cost' => 0]);
        DB::table('researches')->update(['supply_cost' => 0]);
        DB::table('ships')->update(['supply_cost' => 0]);
    }

    // ── getFreeSupply ─────────────────────────────────────────────────────────

    /**
     * getFreeSupply() must return a negative value when used supply exceeds cap.
     *
     * Setup: all costs zeroed, oremine supply_cost=2, colony 1 oremine level=5
     * → used=10, cap=0 → free=-10.
     */
    public function test_get_free_supply_returns_negative_when_over_cap(): void
    {
        $this->zeroAllSupplyCosts();
        DB::table('buildings')->where('id', 27)->update(['supply_cost' => 2]);
        DB::table('colony_buildings')
            ->where('colony_id', 1)->where('building_id', 27)
            ->update(['level' => 5]);
        DB::table('user_resources')->where('user_id', 3)->update(['supply' => 0]);

        /** @var ResourcesService $svc */
        $svc = $this->app->make(ResourcesService::class);

        $free = $svc->getFreeSupply(1);

        $this->assertLessThan(0, $free, 'getFreeSupply() must be negative when used > cap');
    }

    /**
     * getFreeSupply() must return 0 or more when the colony is within cap.
     *
     * All supply costs are zeroed → used=0; any cap value makes free >= 0.
     */
    public function test_get_free_supply_returns_non_negative_when_within_cap(): void
    {
        $this->zeroAllSupplyCosts();
        DB::table('user_resources')->where('user_id', 3)->update(['supply' => 999]);

        /** @var ResourcesService $svc */
        $svc = $this->app->make(ResourcesService::class);

        $free = $svc->getFreeSupply(1);

        $this->assertGreaterThanOrEqual(0, $free, 'getFreeSupply() must not be negative when within cap');
    }

    // ── getOverCapColonyIds ───────────────────────────────────────────────────

    /**
     * getOverCapColonyIds() returns only the colony IDs where free supply is negative.
     *
     * Colony 1: over-cap (oremine at level=5 × supply_cost=2 = 10, cap=0).
     * Colony 2: within-cap — all entity levels and advisors zeroed so used=0, cap=0 → free=0.
     */
    public function test_get_over_cap_colony_ids_returns_correct_colonies(): void
    {
        $this->zeroAllSupplyCosts();
        DB::table('buildings')->where('id', 27)->update(['supply_cost' => 2]);

        // Colony 1 — over-cap
        DB::table('colony_buildings')
            ->where('colony_id', 1)->where('building_id', 27)
            ->update(['level' => 5]);
        DB::table('user_resources')->where('user_id', 3)->update(['supply' => 0]);

        // Colony 2 — within cap: zero all entity levels and remove its advisors
        // so that used=0, cap=0 → free=0 (not over-cap).
        DB::table('colony_buildings')->where('colony_id', 2)->update(['level' => 0]);
        DB::table('colony_researches')->where('colony_id', 2)->update(['level' => 0]);
        DB::table('colony_ships')->where('colony_id', 2)->update(['level' => 0]);
        DB::table('advisors')->where('colony_id', 2)->delete();

        /** @var ResourcesService $svc */
        $svc = $this->app->make(ResourcesService::class);

        $ids = $svc->getOverCapColonyIds();

        $this->assertContains(1, $ids,    'Colony 1 must be in over-cap list');
        $this->assertNotContains(2, $ids, 'Colony 2 must not be in over-cap list');
    }

    // ── Building decay with overcap ───────────────────────────────────────────

    /**
     * Building status_points must decrease at 2× decay_rate when colony is over cap.
     *
     * oremine (id 27): decay_rate=0.17, overcap_factor=2.0
     * Expected: SP = 10.0 - (0.17 × 2.0) = 9.66
     */
    public function test_building_decays_faster_when_colony_is_over_cap(): void
    {
        $this->zeroAllSupplyCosts();
        DB::table('buildings')->where('id', 27)->update(['supply_cost' => 2]);
        DB::table('colony_buildings')
            ->where('colony_id', 1)->where('building_id', 27)
            ->update(['level' => 5, 'status_points' => 10.0]);
        DB::table('user_resources')->where('user_id', 3)->update(['supply' => 0]);
        // Colony 2 must not interfere — zero all its levels so it isn't over-cap either.
        DB::table('colony_buildings')->where('colony_id', 2)->update(['level' => 0]);

        Artisan::call('game:tick', ['--tick' => 9100]);

        $sp = (float) DB::table('colony_buildings')
            ->where('colony_id', 1)->where('building_id', 27)
            ->value('status_points');

        $this->assertEqualsWithDelta(10.0 - (0.17 * 2.0), $sp, 0.001,
            'Building SP must decrease by rate × overcap_factor when over cap');
    }

    /**
     * Building status_points must decrease at normal rate when colony is within cap.
     *
     * All supply costs zeroed → used=0, cap=0 → free=0, not over-cap.
     * oremine (id 27): decay_rate=0.17
     * Expected: SP = 10.0 - 0.17 = 9.83
     */
    public function test_building_decays_normally_when_colony_is_within_cap(): void
    {
        $this->zeroAllSupplyCosts();
        DB::table('colony_buildings')
            ->where('colony_id', 1)->where('building_id', 27)
            ->update(['level' => 5, 'status_points' => 10.0]);

        Artisan::call('game:tick', ['--tick' => 9101]);

        $sp = (float) DB::table('colony_buildings')
            ->where('colony_id', 1)->where('building_id', 27)
            ->value('status_points');

        $this->assertEqualsWithDelta(10.0 - 0.17, $sp, 0.001,
            'Building SP must decrease by decay_rate only when within cap');
    }

    // ── Research decay with overcap ───────────────────────────────────────────

    /**
     * Research status_points must decrease at 2× decay_rate when colony is over cap.
     *
     * biology (id 33): decay_rate=0.13, overcap_factor=2.0
     * Expected: SP = 15.0 - (0.13 × 2.0) = 14.74
     */
    public function test_research_decays_faster_when_colony_is_over_cap(): void
    {
        $this->zeroAllSupplyCosts();
        DB::table('buildings')->where('id', 27)->update(['supply_cost' => 2]);
        DB::table('colony_buildings')
            ->where('colony_id', 1)->where('building_id', 27)
            ->update(['level' => 5]);
        DB::table('user_resources')->where('user_id', 3)->update(['supply' => 0]);
        DB::table('colony_buildings')->where('colony_id', 2)->update(['level' => 0]);

        DB::table('colony_researches')
            ->where('colony_id', 1)->where('research_id', 33)
            ->update(['level' => 2, 'status_points' => 15.0]);

        Artisan::call('game:tick', ['--tick' => 9110]);

        $sp = (float) DB::table('colony_researches')
            ->where('colony_id', 1)->where('research_id', 33)
            ->value('status_points');

        $this->assertEqualsWithDelta(15.0 - (0.13 * 2.0), $sp, 0.001,
            'Research SP must decrease by rate × overcap_factor when over cap');
    }

    /**
     * Research status_points must decrease at normal rate when colony is within cap.
     *
     * All supply costs zeroed → used=0, not over-cap.
     * biology (id 33): decay_rate=0.13
     * Expected: SP = 15.0 - 0.13 = 14.87
     */
    public function test_research_decays_normally_when_colony_is_within_cap(): void
    {
        $this->zeroAllSupplyCosts();
        DB::table('colony_researches')
            ->where('colony_id', 1)->where('research_id', 33)
            ->update(['level' => 2, 'status_points' => 15.0]);

        Artisan::call('game:tick', ['--tick' => 9111]);

        $sp = (float) DB::table('colony_researches')
            ->where('colony_id', 1)->where('research_id', 33)
            ->value('status_points');

        $this->assertEqualsWithDelta(15.0 - 0.13, $sp, 0.001,
            'Research SP must decrease by decay_rate only when within cap');
    }
}
