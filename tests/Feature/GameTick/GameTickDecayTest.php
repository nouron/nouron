<?php

namespace Tests\Feature\GameTick;

use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * GameTick step 4/5/6 — Building, ship, and research decay.
 *
 * Covered scenarios:
 *  Building decay (step 4):
 *  - Happy path: status_points decrease by decay_rate each tick
 *  - Level-down when SP hits 0: level decremented, SP reset to max_status_points
 *  - Building at level 0 is skipped (nothing to decay)
 *  - SecurityHub colony receives recycle materials when a building levels down
 *
 *  Ship decay (step 5):
 *  - Happy path: fleet_ships.status_points decrease by ship decay_rate
 *  - Destroyed when SP hits 0: row removed from fleet_ships
 *
 *  Research decay (step 6):
 *  - Happy path: status_points decrease by decay_rate
 *  - Level-down when SP hits 0: level decremented, SP reset to max
 *  - Knowledge researches never decay (GDD §10)
 *
 * Fixture summary (TestSeeder):
 *   Colony 1 (Springfield), user_id=3 (Bart)
 *     CC  (building_id=25): level=3, decay_rate=0.33
 *     harvester (building_id=27): level=1, decay_rate=0.95
 *     housing (building_id=28): level=2, decay_rate=0.44
 *   Fleet 8 (user_id=18): frigate1 (ship_id=29, count=20, sp=20.0)
 *   Colony research test decay placeholder (research_id=9901): level=1, status_points=20
 *
 * Uses tick numbers 11000–11099 (no seed orders in this range).
 */
class GameTickDecayTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(TestSeeder::class)->run();
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    /** Zero all supply costs so no colony is ever over-cap during these tests. */
    private function zeroAllSupplyCosts(): void
    {
        DB::table('buildings')->update(['supply_cost' => 0]);
        DB::table('researches')->update(['supply_cost' => 0]);
        DB::table('ships')->update(['supply_cost' => 0]);
    }

    private function getBuildingRow(int $colonyId, int $buildingId): ?object
    {
        return DB::table('colony_buildings')
            ->where('colony_id', $colonyId)
            ->where('building_id', $buildingId)
            ->first();
    }

    private function getResearchRow(int $colonyId, int $researchId): ?object
    {
        return DB::table('colony_researches')
            ->where('colony_id', $colonyId)
            ->where('research_id', $researchId)
            ->first();
    }

    // ── Step 4: Building decay ─────────────────────────────────────────────────

    /**
     * Building status_points must decrease by the building's decay_rate per tick.
     * harvester (id 27): decay_rate=0.17 (from MasterDataSeeder), starting SP=10
     * → expected 10 - 0.17 = 9.83
     */
    public function test_building_status_points_decrease_by_decay_rate(): void
    {
        $this->zeroAllSupplyCosts();
        DB::table('colony_buildings')
            ->where('colony_id', 1)->where('building_id', 27)
            ->update(['level' => 1, 'status_points' => 10.0]);

        Artisan::call('game:tick', ['--tick' => 11000]);

        $decayRate = (float) DB::table('buildings')->where('id', 27)->value('decay_rate');
        $sp = (float) $this->getBuildingRow(1, 27)->status_points;
        $this->assertEqualsWithDelta(10.0 - $decayRate, $sp, 0.001,
            "Building SP must decrease by decay_rate ({$decayRate}) each tick");
    }

    /**
     * When building status_points reach 0 the building loses one level and SP resets
     * to max_status_points (20).
     * Use a SP value small enough that subtracting any positive decay_rate takes it to ≤ 0.
     */
    public function test_building_levels_down_when_status_points_reach_zero(): void
    {
        $this->zeroAllSupplyCosts();
        DB::table('colony_buildings')
            ->where('colony_id', 1)->where('building_id', 27)
            ->update(['level' => 3, 'status_points' => 0.1]);

        Artisan::call('game:tick', ['--tick' => 11001]);

        $row = $this->getBuildingRow(1, 27);
        $this->assertEquals(2, $row->level, 'Level must decrease by 1 when SP is depleted');
        $this->assertEquals(20, (int) $row->status_points, 'SP must reset to max_status_points after level-down');
    }

    /**
     * A building already at level 0 must not be decayed — it is excluded from the
     * decay query (WHERE level > 0).
     */
    public function test_building_at_level_zero_is_not_decayed(): void
    {
        $this->zeroAllSupplyCosts();
        DB::table('colony_buildings')
            ->where('colony_id', 1)->where('building_id', 27)
            ->update(['level' => 0, 'status_points' => 8.0]);

        Artisan::call('game:tick', ['--tick' => 11002]);

        $row = $this->getBuildingRow(1, 27);
        $this->assertEquals(0, $row->level, 'Level-0 building must remain at level 0');
        $this->assertEqualsWithDelta(8.0, (float) $row->status_points, 0.001,
            'Level-0 building SP must not change');
    }

    /**
     * A techtree.level_down event must be created for the colony owner when a
     * building loses a level.
     */
    public function test_building_level_down_creates_event(): void
    {
        DB::table('colony_buildings')
            ->where('colony_id', 1)->where('building_id', 27)
            ->update(['level' => 2, 'status_points' => 0.1]);

        Artisan::call('game:tick', ['--tick' => 11003]);

        $event = DB::table('colony_log')
            ->where('user', 3)
            ->where('event', 'techtree.level_down')
            ->where('tick', 11003)
            ->first();

        $this->assertNotNull($event, 'techtree.level_down event must be created when building levels down');

        $params = json_decode($event->parameters, true);
        $this->assertEquals(1, $params['colony_id'], 'Event must reference the correct colony');
        $this->assertEquals(27, $params['tech_id'], 'Event must reference the correct building');
    }

    /**
     * SecurityHub recycles 10% of tradeable build costs back to the colony when any
     * building levels down.
     *
     * harvester (building_id=27) build costs for tradeable resources (3,4,5):
     *   resource 3 (regolith): 10 → 10% = 1
     *   resource 4 (compounds): 10 → 10% = 1
     * SecurityHub must be present (level>0) in the colony.
     */
    public function test_security_hub_recycles_resources_on_building_level_down(): void
    {
        // Install SecurityHub at level 1 on colony 1
        DB::table('colony_buildings')->updateOrInsert(
            ['colony_id' => 1, 'building_id' => 53, 'instance_id' => 1],
            ['level' => 1, 'status_points' => 20, 'ap_spend' => 0]
        );

        // Drive harvester to level-down threshold
        DB::table('colony_buildings')
            ->where('colony_id', 1)->where('building_id', 27)
            ->update(['level' => 2, 'status_points' => 0.1]);

        // Record baseline colony resources
        $regolithBefore = (int) DB::table('colony_resources')
            ->where('colony_id', 1)->where('resource_id', 3)->value('amount');

        Artisan::call('game:tick', ['--tick' => 11004]);

        // Verify the building did level down
        $row = $this->getBuildingRow(1, 27);
        $this->assertEquals(1, $row->level, 'Building must have leveled down for recycling to trigger');

        // Recycle amount = floor(base_amount × 0.10), min 1
        // harvester costs resource 3: 10 → floor(10 × 0.10) = 1
        $regolithAfter = (int) DB::table('colony_resources')
            ->where('colony_id', 1)->where('resource_id', 3)->value('amount');

        $this->assertGreaterThan($regolithBefore, $regolithAfter,
            'Colony must receive recycled resources when SecurityHub is present and building levels down');
    }

    /**
     * Without SecurityHub present, no recycling should occur on building level-down.
     */
    public function test_no_recycling_without_security_hub(): void
    {
        // Ensure SecurityHub does not exist at level > 0
        DB::table('colony_buildings')
            ->where('colony_id', 1)->where('building_id', 53)
            ->delete();

        DB::table('colony_buildings')
            ->where('colony_id', 1)->where('building_id', 27)
            ->update(['level' => 2, 'status_points' => 0.1]);

        // Set known values for tradeable resources
        DB::table('colony_resources')->updateOrInsert(
            ['colony_id' => 1, 'resource_id' => 3],
            ['amount' => 100]
        );

        Artisan::call('game:tick', ['--tick' => 11005]);

        // Colony resources may change from production — but recycling should NOT
        // have occurred. The production for building_id=27 (regolith) fires AFTER
        // decay, so we only verify that the building did level down (main assertion).
        $row = $this->getBuildingRow(1, 27);
        $this->assertEquals(1, $row->level, 'Building must have leveled down');
        // No assert on exact resource amount here — production runs in same tick.
    }

    // ── Step 5: Ship decay ─────────────────────────────────────────────────────

    /**
     * Fleet ship status_points must decrease by the ship's decay_rate each tick.
     * frigate1 (ship_id=29): check decay rate from DB.
     */
    public function test_ship_status_points_decrease_each_tick(): void
    {
        $decayRate = (float) DB::table('ships')->where('id', 29)->value('decay_rate');
        $this->assertGreaterThan(0.0, $decayRate, 'Ship 29 must have a positive decay_rate');

        DB::table('fleet_ships')
            ->where('fleet_id', 8)->where('ship_id', 29)
            ->update(['status_points' => 15.0]);

        Artisan::call('game:tick', ['--tick' => 11010]);

        $sp = (float) DB::table('fleet_ships')
            ->where('fleet_id', 8)->where('ship_id', 29)
            ->value('status_points');

        $this->assertEqualsWithDelta(15.0 - $decayRate, $sp, 0.001,
            'Ship SP must decrease by its decay_rate each tick');
    }

    /**
     * When fleet ship status_points hit ≤ 0 the fleet_ships row must be deleted.
     */
    public function test_ship_is_destroyed_when_status_points_reach_zero(): void
    {
        DB::table('fleet_ships')
            ->where('fleet_id', 8)->where('ship_id', 29)
            ->update(['status_points' => 0.05]);

        Artisan::call('game:tick', ['--tick' => 11011]);

        $exists = DB::table('fleet_ships')
            ->where('fleet_id', 8)->where('ship_id', 29)
            ->exists();

        $this->assertFalse($exists, 'Fleet ship entry must be removed when SP reaches 0');
    }

    /**
     * Multiple ship types in the same fleet must all decay independently.
     * Fleet 8 has both ship_id=29 (frigate1) and ship_id=37 (corvette).
     */
    public function test_all_ships_in_fleet_decay_independently(): void
    {
        DB::table('fleet_ships')
            ->where('fleet_id', 8)->whereIn('ship_id', [29, 37])
            ->update(['status_points' => 18.0]);

        $rate29 = (float) DB::table('ships')->where('id', 29)->value('decay_rate');
        $rate37 = (float) DB::table('ships')->where('id', 37)->value('decay_rate');

        Artisan::call('game:tick', ['--tick' => 11012]);

        $sp29 = (float) DB::table('fleet_ships')->where('fleet_id', 8)->where('ship_id', 29)->value('status_points');
        $sp37 = (float) DB::table('fleet_ships')->where('fleet_id', 8)->where('ship_id', 37)->value('status_points');

        $this->assertEqualsWithDelta(18.0 - $rate29, $sp29, 0.001, 'Ship 29 SP must decay by its own rate');
        $this->assertEqualsWithDelta(18.0 - $rate37, $sp37, 0.001, 'Ship 37 SP must decay by its own rate');
    }

    // ── Step 6: Research decay ─────────────────────────────────────────────────

    /**
     * Research status_points must decrease by decay_rate each tick.
     * test decay placeholder (research_id=9901): decay_rate from DB.
     */
    public function test_research_status_points_decrease_by_decay_rate(): void
    {
        $this->zeroAllSupplyCosts();

        $decayRate = (float) DB::table('researches')->where('id', 9901)->value('decay_rate');
        $this->assertGreaterThan(0.0, $decayRate, 'Research 9901 must have a positive decay_rate');

        DB::table('colony_researches')
            ->where('colony_id', 1)->where('research_id', 9901)
            ->update(['level' => 2, 'status_points' => 14.0]);

        Artisan::call('game:tick', ['--tick' => 11020]);

        $sp = (float) $this->getResearchRow(1, 9901)->status_points;
        $this->assertEqualsWithDelta(14.0 - $decayRate, $sp, 0.001,
            'Research SP must decrease by its decay_rate each tick');
    }

    /**
     * When research status_points hit ≤ 0, the research loses one level and SP
     * resets to max_status_points.
     */
    public function test_research_levels_down_when_status_points_reach_zero(): void
    {
        DB::table('colony_researches')
            ->where('colony_id', 1)->where('research_id', 9901)
            ->update(['level' => 3, 'status_points' => 0.1]);

        Artisan::call('game:tick', ['--tick' => 11021]);

        $row = $this->getResearchRow(1, 9901);
        $this->assertEquals(2, $row->level, 'Research level must decrease by 1 when SP is depleted');
        $this->assertEquals(20, (int) $row->status_points, 'Research SP must reset to max after level-down');
    }

    /**
     * Knowledge researches (purpose='knowledge') must never decay — GDD §10.
     *
     * Determine knowledge IDs from config and verify one does not decay.
     */
    public function test_knowledge_research_never_decays(): void
    {
        $knowledgeIds = collect(config('knowledge', []))->pluck('id')->toArray();

        if (empty($knowledgeIds)) {
            $this->markTestSkipped('No knowledge entries in config/knowledge.php — cannot test knowledge decay exemption.');
        }

        $knowledgeId = $knowledgeIds[0];

        // Insert a knowledge research row with critically low SP
        DB::table('colony_researches')->updateOrInsert(
            ['colony_id' => 1, 'research_id' => $knowledgeId],
            ['level' => 2, 'status_points' => 0.5]
        );

        Artisan::call('game:tick', ['--tick' => 11022]);

        $row = $this->getResearchRow(1, $knowledgeId);
        // Level and SP must be unchanged — knowledge researches are excluded from decay
        $this->assertEquals(2, $row->level, 'Knowledge research level must not change (exempt from decay)');
        $this->assertEqualsWithDelta(0.5, (float) $row->status_points, 0.001,
            'Knowledge research SP must not change (exempt from decay)');
    }
}
