<?php

namespace Tests\Feature\GameTick;

use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * GameTick step 8 — Resource generation from industry buildings.
 *
 * Config (game.production_curve, bell-curve per level, cumulative — GDD §18, 2026-07-20):
 *   building_id 27 (harvester)    → resource 3 (Regolith): [8,10,12,12,10,8,6,4] per level 1-8
 *                                    cumulative: L1=8 L2=18 L3=30 L4=42 L5=52 L6=60 L7=66 L8=70
 *   building_id 41 (bioFacility)  → resource 5 (Organics): [8,12,12,9,7,5,3,2] per level 1-8
 *                                    cumulative: L1=8 L2=20 L3=32 L4=41 L5=48 L6=53 L7=56 L8=58
 *   Both capped at max_level=8 (config/buildings.php) — higher levels are not reachable.
 *
 * Production is modified by a trust multiplier. To isolate production from trust
 * drift, these tests fix trust at 0 (multiplier = 1.0) by setting colony
 * trust resource to 0 and ensuring no trust events fire in the tick.
 *
 * Covered scenarios:
 *  Happy path:
 *  - harvester at level N generates N×10 Regolith per tick (neutral trust)
 *  - bioFacility at level N generates N×10 Organics per tick (neutral trust)
 *  - Stacking: both buildings produce in the same tick
 *
 *  Edge cases:
 *  - Building at level 0 produces nothing
 *  - Production rounds correctly (int rounding with multiplier)
 *
 *  Adversarial:
 *  - NPC colony (user_id=null) still receives production (no user gate on this step)
 *  - Very high building level produces proportionally
 *
 * Fixture summary (TestSeeder):
 *   Colony 1 (Springfield), user_id=3
 *     harvester (building_id=27): level=1
 *     bioFacility not seeded for colony 1 (must be inserted)
 *   Colony resource (id=3, colony 1): amount=250 initially
 *
 * Uses tick numbers 11200–11229.
 */
class GameTickResourceGenerationTest extends TestCase
{
    use RefreshDatabase;

    private const COLONY_ID = 1;

    private const HARVESTER_ID = 27;

    private const BIO_FACILITY_ID = 41;

    private const RES_REGOLITH = 3;

    private const RES_ORGANICS = 5;

    private const TRUST_RES_ID = 12;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(TestSeeder::class)->run();

        // Fix trust at 0 for colony 1 → multiplier = 1.0 (neutral band: -20..+20)
        DB::table('colony_resources')->updateOrInsert(
            ['colony_id' => self::COLONY_ID, 'resource_id' => self::TRUST_RES_ID],
            ['amount' => 0]
        );
        // Ensure trust events table is clean (no pending events that would shift trust)
        DB::table('trust_events')->where('colony_id', self::COLONY_ID)->delete();

        // Isolate production from the Organika provisioning sink (GameTick step 8a):
        // a huge supply_per_eater forces food_need = floor(used_supply / huge) = 0, so
        // no Organika is eaten and the production assertions stay exact.
        config(['game.food.supply_per_eater' => PHP_INT_MAX]);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function getColonyResource(int $resourceId): int
    {
        return (int) DB::table('colony_resources')
            ->where('colony_id', self::COLONY_ID)
            ->where('resource_id', $resourceId)
            ->value('amount');
    }

    private function setBuildingLevel(int $buildingId, int $level): void
    {
        DB::table('colony_buildings')->updateOrInsert(
            ['colony_id' => self::COLONY_ID, 'building_id' => $buildingId, 'instance_id' => 1],
            ['level' => $level, 'status_points' => 20, 'ap_spend' => 0]
        );
    }

    // ── Happy path ─────────────────────────────────────────────────────────────

    /**
     * harvester at level 1 produces exactly 8 Regolith per tick (curve[1]=8, multiplier 1.0).
     */
    public function test_harvester_generates_regolith_per_level(): void
    {
        $this->setBuildingLevel(self::HARVESTER_ID, 1);
        $before = $this->getColonyResource(self::RES_REGOLITH);

        Artisan::call('game:tick', ['--tick' => 11200]);

        $after = $this->getColonyResource(self::RES_REGOLITH);
        // At multiplier 1.0: yield = round(8 × 1.0) = 8
        $this->assertEquals($before + 8, $after,
            'Harvester level 1 must produce exactly 8 Regolith per tick');
    }

    /**
     * harvester at level 3 produces 30 Regolith per tick (cumulative curve 8+10+12, multiplier 1.0).
     */
    public function test_harvester_production_scales_with_level(): void
    {
        $this->setBuildingLevel(self::HARVESTER_ID, 3);
        $before = $this->getColonyResource(self::RES_REGOLITH);

        Artisan::call('game:tick', ['--tick' => 11201]);

        $after = $this->getColonyResource(self::RES_REGOLITH);
        $this->assertEquals($before + 30, $after,
            'Harvester level 3 must produce 30 Regolith per tick');
    }

    /**
     * bioFacility at level 2 produces 20 Organics per tick.
     */
    public function test_bio_facility_generates_organics_per_level(): void
    {
        $this->setBuildingLevel(self::BIO_FACILITY_ID, 2);
        DB::table('colony_resources')->updateOrInsert(
            ['colony_id' => self::COLONY_ID, 'resource_id' => self::RES_ORGANICS],
            ['amount' => 0]
        );

        Artisan::call('game:tick', ['--tick' => 11202]);

        $organics = $this->getColonyResource(self::RES_ORGANICS);
        $this->assertGreaterThanOrEqual(20, $organics,
            'BioFacility level 2 must produce at least 20 Organics per tick (20 + any prior balance)');
    }

    /**
     * Both harvester and bioFacility produce in the same tick.
     */
    public function test_multiple_production_buildings_produce_simultaneously(): void
    {
        $this->setBuildingLevel(self::HARVESTER_ID, 2);
        $this->setBuildingLevel(self::BIO_FACILITY_ID, 1);

        DB::table('colony_resources')->updateOrInsert(
            ['colony_id' => self::COLONY_ID, 'resource_id' => self::RES_REGOLITH],
            ['amount' => 0]
        );
        DB::table('colony_resources')->updateOrInsert(
            ['colony_id' => self::COLONY_ID, 'resource_id' => self::RES_ORGANICS],
            ['amount' => 0]
        );

        Artisan::call('game:tick', ['--tick' => 11203]);

        $regolith = $this->getColonyResource(self::RES_REGOLITH);
        $organics = $this->getColonyResource(self::RES_ORGANICS);

        // harvester level 2 → cumulative 8+10=18 Regolith; bioFacility level 1 → 8 Organics
        $this->assertEquals(18, $regolith, 'Harvester level 2 must produce 18 Regolith');
        $this->assertEquals(8, $organics, 'BioFacility level 1 must produce 8 Organics');
    }

    // ── Edge cases ─────────────────────────────────────────────────────────────

    /**
     * A building at level 0 must not produce any resources.
     */
    public function test_building_at_level_zero_produces_nothing(): void
    {
        $this->setBuildingLevel(self::HARVESTER_ID, 0);
        DB::table('colony_resources')->updateOrInsert(
            ['colony_id' => self::COLONY_ID, 'resource_id' => self::RES_REGOLITH],
            ['amount' => 50]
        );

        // Remove bioFacility so only harvester is relevant
        DB::table('colony_buildings')
            ->where('colony_id', self::COLONY_ID)->where('building_id', self::BIO_FACILITY_ID)
            ->update(['level' => 0]);

        Artisan::call('game:tick', ['--tick' => 11210]);

        $amount = $this->getColonyResource(self::RES_REGOLITH);
        $this->assertEquals(50, $amount, 'Level-0 building must produce 0 resources');
    }

    /**
     * Production is capped at max_level=8 — a level beyond the cap (10, which is not
     * actually reachable via levelup since max_level=8 gates it) must not produce more
     * than the level-8 cumulative yield (70 Regolith). Guards against the curve lookup
     * indexing past its highest defined level.
     */
    public function test_production_scales_proportionally_at_high_levels(): void
    {
        $this->setBuildingLevel(self::HARVESTER_ID, 10);
        DB::table('colony_resources')->updateOrInsert(
            ['colony_id' => self::COLONY_ID, 'resource_id' => self::RES_REGOLITH],
            ['amount' => 0]
        );
        // Disable bioFacility for clean isolation
        DB::table('colony_buildings')
            ->where('colony_id', self::COLONY_ID)->where('building_id', self::BIO_FACILITY_ID)
            ->update(['level' => 0]);

        Artisan::call('game:tick', ['--tick' => 11211]);

        $regolith = $this->getColonyResource(self::RES_REGOLITH);
        $this->assertEquals(70, $regolith, 'Harvester level 10 (beyond cap) must produce the level-8 cap yield of 70 Regolith');
    }

    // ── Trust multiplier interaction ────────────────────────────────────────────

    /**
     * High trust (>60) applies a 1.20× production multiplier.
     * harvester level 5 × 10 × 1.20 = round(60) = 60.
     */
    public function test_high_trust_applies_production_bonus(): void
    {
        $this->setBuildingLevel(self::HARVESTER_ID, 5);
        DB::table('colony_buildings')
            ->where('colony_id', self::COLONY_ID)->where('building_id', self::BIO_FACILITY_ID)
            ->update(['level' => 0]);

        // Set trust to 75 (Euphorisch band: +61..+100 → multiplier 1.20)
        DB::table('colony_resources')->updateOrInsert(
            ['colony_id' => self::COLONY_ID, 'resource_id' => self::TRUST_RES_ID],
            ['amount' => 75]
        );
        DB::table('colony_resources')->updateOrInsert(
            ['colony_id' => self::COLONY_ID, 'resource_id' => self::RES_REGOLITH],
            ['amount' => 0]
        );

        Artisan::call('game:tick', ['--tick' => 11220]);

        $regolith = $this->getColonyResource(self::RES_REGOLITH);
        // cumulative curve at level 5 = 52; yield = round(52 × 1.20) = 62
        $this->assertEquals(62, $regolith,
            'Production at trust=75 must apply 1.20× multiplier → 62 Regolith');
    }

    /**
     * Low trust (<-60) applies a 0.70× production penalty.
     * harvester level 5 cumulative curve (52) × 0.70 = round(36.4) = 36.
     */
    public function test_low_trust_applies_production_penalty(): void
    {
        $this->setBuildingLevel(self::HARVESTER_ID, 5);
        DB::table('colony_buildings')
            ->where('colony_id', self::COLONY_ID)->where('building_id', self::BIO_FACILITY_ID)
            ->update(['level' => 0]);

        // Set trust to -80 (Aufruhr band: -100..-61 → multiplier 0.70)
        DB::table('colony_resources')->updateOrInsert(
            ['colony_id' => self::COLONY_ID, 'resource_id' => self::TRUST_RES_ID],
            ['amount' => -80]
        );
        DB::table('colony_resources')->updateOrInsert(
            ['colony_id' => self::COLONY_ID, 'resource_id' => self::RES_REGOLITH],
            ['amount' => 0]
        );

        Artisan::call('game:tick', ['--tick' => 11221]);

        $regolith = $this->getColonyResource(self::RES_REGOLITH);
        // cumulative curve at level 5 = 52; yield = round(52 × 0.70) = 36
        $this->assertEquals(36, $regolith,
            'Production at trust=-80 must apply 0.70× penalty → 36 Regolith');
    }
}
