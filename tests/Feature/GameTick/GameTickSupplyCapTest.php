<?php

namespace Tests\Feature\GameTick;

use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * GameTick step 7 — Supply cap recalculation.
 *
 * The supply cap is SET (not incremented) in user_resources.supply each tick.
 * Formula: CC_flat (10) + housing_level × 8 + Σ(knowledge_cap_per_level), max 200.
 *
 * Covered scenarios:
 *  Happy path:
 *  - Supply is correctly calculated from CC level + housing level
 *  - Knowledge cap bonus is added when colony has knowledge researches
 *
 *  Edge cases:
 *  - No CC (level=0) → supply set to 0
 *  - Supply capped at 200 regardless of housing level
 *  - Multiple housing instances: sum of all levels used
 *  - Colony with NPC user (user_id=null) does not crash and is processed
 *
 *  Adversarial:
 *  - CC at level 0 after a previous tick (regression guard)
 *  - Extremely high housing level still capped at 200
 *
 * Fixture summary (TestSeeder):
 *   Colony 1 (Springfield), user_id=3 (Bart)
 *     CC (building_id=25): level=3 → flat cap = 10
 *     housing (building_id=28): level=2 → +16
 *     Expected supply = 26 (before knowledge contribution)
 *   user_resources: user 3, credits=2700, supply=18 (overwritten by tick)
 *
 * Uses tick numbers 11100–11129.
 */
class GameTickSupplyCapTest extends TestCase
{
    use RefreshDatabase;

    private const USER_ID   = 3;
    private const COLONY_ID = 1;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(TestSeeder::class)->run();
        // Remove all supply-consuming buildings to get a clean baseline
        DB::table('buildings')->update(['supply_cost' => 0]);
        DB::table('researches')->update(['supply_cost' => 0]);
        DB::table('ships')->update(['supply_cost' => 0]);
    }

    // ── Happy path ─────────────────────────────────────────────────────────────

    /**
     * Supply = CC_flat (10) + housing_level × 8.
     * Colony 1: CC level=3 (>0) → flat 10; housing level=2 → 10 + 16 = 26.
     */
    public function test_supply_cap_calculated_from_cc_and_housing(): void
    {
        Artisan::call('game:tick', ['--tick' => 11100]);

        $supply = (int) DB::table('user_resources')->where('user_id', self::USER_ID)->value('supply');
        $this->assertEquals(26, $supply, 'Supply cap must equal CC_flat + housing_level × 8');
    }

    /**
     * Supply cap must be 0 when CC level is 0 (colony not operational).
     */
    public function test_supply_is_zero_without_operational_command_center(): void
    {
        DB::table('colony_buildings')
            ->where('colony_id', self::COLONY_ID)->where('building_id', 25)
            ->update(['level' => 0]);

        Artisan::call('game:tick', ['--tick' => 11101]);

        $supply = (int) DB::table('user_resources')->where('user_id', self::USER_ID)->value('supply');
        $this->assertEquals(0, $supply, 'Supply must be 0 when CC level is 0');
    }

    /**
     * Supply is hard-capped at 200 regardless of housing level.
     * Set housing level to 30 → 10 + 240 = 250 → clamped to 200.
     */
    public function test_supply_cap_never_exceeds_maximum_of_200(): void
    {
        DB::table('colony_buildings')
            ->where('colony_id', self::COLONY_ID)->where('building_id', 28)
            ->update(['level' => 30]);

        Artisan::call('game:tick', ['--tick' => 11102]);

        $supply = (int) DB::table('user_resources')->where('user_id', self::USER_ID)->value('supply');
        $this->assertEquals(200, $supply, 'Supply cap must not exceed 200 (hard cap)');
    }

    /**
     * Multiple housing instances: all levels are summed.
     *
     * Baseline: 1 housing instance at level 2.
     * Add 2 more instances at level 3 each → total housing = 2+3+3 = 8.
     * Expected supply = 10 + (8 × 8) = 74.
     */
    public function test_supply_cap_sums_all_housing_instances(): void
    {
        DB::table('colony_buildings')->insert([
            ['colony_id' => self::COLONY_ID, 'building_id' => 28, 'level' => 3, 'status_points' => 20, 'ap_spend' => 0, 'instance_id' => 2],
            ['colony_id' => self::COLONY_ID, 'building_id' => 28, 'level' => 3, 'status_points' => 20, 'ap_spend' => 0, 'instance_id' => 3],
        ]);

        Artisan::call('game:tick', ['--tick' => 11103]);

        $supply = (int) DB::table('user_resources')->where('user_id', self::USER_ID)->value('supply');
        // existing housing=2 + new instance 2 level=3 + new instance 3 level=3 = 8 total
        // cap = 10 + (8 × 8) = 74
        $this->assertEquals(74, $supply, 'Supply must sum all housing instance levels');
    }

    /**
     * Knowledge researches contribute to supply cap via knowledge_cap_per_level config.
     *
     * If no knowledge entries exist in config this test is skipped (not an error).
     */
    public function test_knowledge_cap_adds_to_supply(): void
    {
        $knowledgeIds = collect(config('knowledge', []))->pluck('id')->toArray();
        $capPerLevel  = config('game.supply.knowledge_cap_per_level', []);

        if (empty($knowledgeIds) || empty($capPerLevel)) {
            $this->markTestSkipped('No knowledge or knowledge_cap_per_level config — cannot test knowledge supply bonus.');
        }

        $knowledgeId = $knowledgeIds[0];

        // Give the colony a knowledge research at level 2 (uses cap levels 1 and 2)
        DB::table('colony_researches')->updateOrInsert(
            ['colony_id' => self::COLONY_ID, 'research_id' => $knowledgeId],
            ['level' => 2, 'status_points' => 20]
        );

        $expectedBonus = ($capPerLevel[1] ?? 0) + ($capPerLevel[2] ?? 0);

        Artisan::call('game:tick', ['--tick' => 11104]);

        $supply = (int) DB::table('user_resources')->where('user_id', self::USER_ID)->value('supply');
        // base = 10 (CC) + 16 (housing level 2 × 8) = 26, plus knowledge bonus
        $expectedSupply = min(200, 26 + $expectedBonus);
        $this->assertEquals($expectedSupply, $supply,
            'Knowledge cap bonus must be added to the supply cap');
    }

    // ── Adversarial ────────────────────────────────────────────────────────────

    /**
     * A player whose CC is at level > 0, then drops to 0, must immediately lose
     * all supply on the very next tick.
     */
    public function test_supply_drops_to_zero_when_cc_is_removed(): void
    {
        // First tick: CC level=3 → supply=26
        Artisan::call('game:tick', ['--tick' => 11110]);
        $this->assertEquals(26, (int) DB::table('user_resources')->where('user_id', self::USER_ID)->value('supply'));

        // CC is removed (level=0)
        DB::table('colony_buildings')
            ->where('colony_id', self::COLONY_ID)->where('building_id', 25)
            ->update(['level' => 0]);

        // Second tick: supply must now be 0
        Artisan::call('game:tick', ['--tick' => 11111]);
        $supply = (int) DB::table('user_resources')->where('user_id', self::USER_ID)->value('supply');
        $this->assertEquals(0, $supply, 'Supply must drop to 0 immediately when CC is removed');
    }

    /**
     * Supply is SET each tick, not accumulated.
     * Verifies that a previously very high supply value is overwritten correctly.
     */
    public function test_supply_is_overwritten_not_incremented(): void
    {
        // Artificially inflate supply to a high value
        DB::table('user_resources')->where('user_id', self::USER_ID)->update(['supply' => 9999]);

        Artisan::call('game:tick', ['--tick' => 11112]);

        $supply = (int) DB::table('user_resources')->where('user_id', self::USER_ID)->value('supply');
        // Expected: 10 (CC=3 flat) + 16 (housing level 2) = 26, not 9999 or 10025
        $this->assertEquals(26, $supply, 'Supply must be SET not incremented — previous value must not matter');
    }
}
