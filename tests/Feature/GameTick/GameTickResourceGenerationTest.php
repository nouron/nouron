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
 * bioFacility (building_id 41) still follows the level-based bell curve
 * (game.production_curve[41], GDD §18, 2026-07-20): [8,12,12,9,7,5,3,2] per
 * level 1-8, cumulative — L1=8 L2=20 L3=32 …, capped at max_level=8.
 *
 * Harvester (building_id 27) production_curve is INERT since the §4c depletion
 * mechanic (2026-08-03) — the Harvester no longer levels up (max_level=1,
 * GDD §13.5) and instead produces from the specific regolith tile it is placed
 * on: Ertrag = Frischwert × (0,5 + 0,5 × Restvorkommen / resource_max), see
 * GameTick::harvesterYield() and GameTick::generateHarvesterYield(). Full
 * depletion/geology-bonus coverage lives in HarvesterDepletionTest — this file
 * covers only the interaction with the level-0 gate, the trust multiplier,
 * and simultaneous production alongside bioFacility.
 *
 * Production is modified by a trust multiplier. To isolate production from trust
 * drift, these tests fix trust at 0 (multiplier = 1.0) by setting colony
 * trust resource to 0 and ensuring no trust events fire in the tick.
 *
 * Covered scenarios:
 *  Happy path:
 *  - harvester placed on a full-reserve regolith_normal tile produces its fresh value (18)
 *  - bioFacility at level N generates N×10 Organics per tick (neutral trust)
 *  - Stacking: both buildings produce in the same tick
 *
 *  Edge cases:
 *  - Building at level 0 produces nothing even when placed on a producing tile
 *  - Yield does not depend on the stored level value beyond the level>0 gate
 *
 *  Adversarial:
 *  - NPC colony (user_id=null) still receives production (no user gate on this step)
 *
 * Fixture summary (TestSeeder):
 *   Colony 1 (Springfield), user_id=3
 *     harvester (building_id=27): level=1, unplaced (tile_x=NULL) by default
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

    private const HARVESTER_TILE_Q = 3;

    private const HARVESTER_TILE_R = 0;

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

    /**
     * Places the Harvester (instance 1) on a fresh regolith_normal tile
     * (fresh_yield 23, resource_max 300) — the fixture's default harvester
     * row has no tile_x/tile_y, so production requires explicit placement
     * under the §4c depletion mechanic.
     */
    private function placeHarvesterOnFreshTile(int $level = 1): void
    {
        DB::table('colony_buildings')->updateOrInsert(
            ['colony_id' => self::COLONY_ID, 'building_id' => self::HARVESTER_ID, 'instance_id' => 1],
            [
                'level' => $level,
                'status_points' => 20,
                'ap_spend' => 0,
                'tile_x' => self::HARVESTER_TILE_Q,
                'tile_y' => self::HARVESTER_TILE_R,
                'pending_until_tick' => null,
            ]
        );

        DB::table('colony_tiles')
            ->where('colony_id', self::COLONY_ID)
            ->where('q', self::HARVESTER_TILE_Q)->where('r', self::HARVESTER_TILE_R)
            ->delete();
        DB::table('colony_tiles')->insert([
            'colony_id' => self::COLONY_ID, 'q' => self::HARVESTER_TILE_Q, 'r' => self::HARVESTER_TILE_R, 'ring' => 3,
            'tile_type' => 'regolith_normal', 'is_explored' => 1, 'is_colony_zone' => 0, 'is_deep_scanned' => 0,
            'resource_amount' => 300, 'resource_max' => 300,
        ]);
    }

    // ── Happy path ─────────────────────────────────────────────────────────────

    /**
     * Harvester placed on a full-reserve regolith_normal tile produces exactly
     * its fresh value (23) per tick at neutral trust — GDD §4c.
     */
    public function test_harvester_generates_regolith_from_placed_tile(): void
    {
        $this->placeHarvesterOnFreshTile();
        $before = $this->getColonyResource(self::RES_REGOLITH);

        Artisan::call('game:tick', ['--tick' => 11200]);

        $after = $this->getColonyResource(self::RES_REGOLITH);
        $this->assertEquals($before + 23, $after,
            'Harvester on a full-reserve regolith_normal tile must produce exactly 23 Regolith per tick');
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
     * Both harvester (tile-based, §4c) and bioFacility (level-based curve) produce
     * in the same tick.
     */
    public function test_multiple_production_buildings_produce_simultaneously(): void
    {
        $this->placeHarvesterOnFreshTile();
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

        // harvester on full-reserve regolith_normal tile → 23 Regolith; bioFacility level 1 → 8 Organics
        $this->assertEquals(23, $regolith, 'Harvester on full-reserve regolith_normal tile must produce 23 Regolith');
        $this->assertEquals(8, $organics, 'BioFacility level 1 must produce 8 Organics');
    }

    // ── Edge cases ─────────────────────────────────────────────────────────────

    /**
     * A building at level 0 must not produce any resources — even when placed
     * on a tile that would otherwise yield Regolith (the level>0 gate applies
     * before the tile-based yield is computed).
     */
    public function test_building_at_level_zero_produces_nothing(): void
    {
        $this->placeHarvesterOnFreshTile(level: 0);
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
     * Harvester no longer levels up beyond 1 (GDD §13.5) — the tile-based yield
     * does not depend on the stored level at all beyond the level>0 gate. A
     * stale/invalid level value (e.g. leftover from before the §13.5 change)
     * must not inflate or otherwise change the yield.
     */
    public function test_harvester_yield_is_independent_of_stored_level_beyond_one(): void
    {
        $this->placeHarvesterOnFreshTile(level: 10);
        DB::table('colony_resources')->updateOrInsert(
            ['colony_id' => self::COLONY_ID, 'resource_id' => self::RES_REGOLITH],
            ['amount' => 0]
        );
        DB::table('colony_buildings')
            ->where('colony_id', self::COLONY_ID)->where('building_id', self::BIO_FACILITY_ID)
            ->update(['level' => 0]);

        Artisan::call('game:tick', ['--tick' => 11211]);

        $regolith = $this->getColonyResource(self::RES_REGOLITH);
        $this->assertEquals(23, $regolith, 'Yield must stay at the tile fresh value regardless of the stored level');
    }

    // ── Trust multiplier interaction ────────────────────────────────────────────

    /**
     * High trust (>60) applies a 1.20× production multiplier.
     * harvester fresh yield 23 × 1.20 = round(27.6) = 28.
     */
    public function test_high_trust_applies_production_bonus(): void
    {
        $this->placeHarvesterOnFreshTile();
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
        // fresh yield 23; yield = round(23 × 1.20) = 28
        $this->assertEquals(28, $regolith,
            'Production at trust=75 must apply 1.20× multiplier → 28 Regolith');
    }

    /**
     * Low trust (<-60) applies a 0.70× production penalty.
     * harvester fresh yield 23 × 0.70 = round(16.1) = 16.
     */
    public function test_low_trust_applies_production_penalty(): void
    {
        $this->placeHarvesterOnFreshTile();
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
        // fresh yield 23; yield = round(23 × 0.70) = 16
        $this->assertEquals(16, $regolith,
            'Production at trust=-80 must apply 0.70× penalty → 16 Regolith');
    }

    // ── agronomy Kenntnis bonus ──────────────────────────────────────────────

    /**
     * agronomy Kenntnis adds a flat, colony-level Organika bonus on top of
     * bioFacility's own level-based production — mirrors geology's Harvester
     * bonus pattern (GDD §13.5 Paritäts-Anforderung).
     */
    public function test_agronomy_knowledge_adds_organika_bonus_on_top_of_biofacility_output(): void
    {
        // bioFacility Lv1 alone yields 8 Organika/Sol (production_curve[41][5][1]).
        $this->setBuildingLevel(self::BIO_FACILITY_ID, 1);
        DB::table('colony_resources')->updateOrInsert(
            ['colony_id' => self::COLONY_ID, 'resource_id' => self::RES_ORGANICS],
            ['amount' => 0]
        );
        // agronomy (research_id=93) Lv3 → cumulative [1,2,2] = 5 bonus Organika/Sol.
        DB::table('colony_researches')->updateOrInsert(
            ['colony_id' => self::COLONY_ID, 'research_id' => 93],
            ['level' => 3, 'ap_spend' => 0, 'status_points' => 20]
        );

        Artisan::call('game:tick', ['--tick' => 11222]);

        $organics = $this->getColonyResource(self::RES_ORGANICS);
        // 8 (base) + 5 (agronomy bonus) = 13, at trust multiplier 1.0 (neutral, fixed in setUp).
        $this->assertEquals(13, $organics,
            'bioFacility Lv1 (8 Organika) + agronomy Lv3 bonus (5 Organika) must total 13 Organika');
    }
}
