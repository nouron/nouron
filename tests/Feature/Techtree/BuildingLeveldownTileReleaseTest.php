<?php

namespace Tests\Feature\Techtree;

use App\Services\ResourcesService;
use App\Services\Techtree\BuildingService;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Manual level-down (Rückbau, colony view) to level 0 takes the building off its
 * tile (A14 Owner decision 2026-09-24): only a freshly placed level-0 building
 * reserves the workplaces of its first level, a demolished one frees tile and
 * reserve. A placed level-0 construction site can be cancelled the same way — the
 * level stays 0, invested AP are forfeited. The Command Center can never be
 * levelled down to 0.
 *
 * Fixture colony 1: CC 25 level 3, sciencelab 31 level 1, harvester 27 level 1.
 * Supply costs pinned in setUp: sciencelab 6, harvester 2.
 */
class BuildingLeveldownTileReleaseTest extends TestCase
{
    use RefreshDatabase;

    private const COLONY_ID = 1;

    private const CC = 25;

    private const HARVESTER = 27;

    private const SCIENCELAB = 31;

    private const INFIRMARY = 46;

    private BuildingService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(TestSeeder::class)->run();
        $this->service = $this->app->make(BuildingService::class);

        config([
            'game.bypass.resource_costs' => true,
            'game.bypass.supply_checks' => false,
        ]);
        DB::table('buildings')->where('id', self::SCIENCELAB)->update(['supply_cost' => 6]);
        DB::table('buildings')->where('id', self::HARVESTER)->update(['supply_cost' => 2]);
        // No cost or prerequisite workarounds needed: a building leveldown charges
        // nothing and does not re-check the levelup prerequisites (the CC's Agrardom,
        // the sciencelab's supply cost row) — BuildingService::leveldownBlocker().
    }

    private function prepare(int $buildingId, int $level, ?int $q, ?int $r): void
    {
        $apForLevelup = (int) DB::table('buildings')->where('id', $buildingId)->value('ap_for_levelup');
        DB::table('colony_buildings')
            ->where('colony_id', self::COLONY_ID)
            ->where('building_id', $buildingId)
            ->update(['level' => $level, 'ap_spend' => $apForLevelup, 'tile_x' => $q, 'tile_y' => $r]);
    }

    private function row(int $buildingId): object
    {
        return DB::table('colony_buildings')
            ->where('colony_id', self::COLONY_ID)
            ->where('building_id', $buildingId)
            ->first();
    }

    private function freeSupply(): int
    {
        return $this->app->make(ResourcesService::class)->getFreeSupply(self::COLONY_ID);
    }

    public function test_leveldown_from_one_to_zero_releases_tile_and_reserve(): void
    {
        $this->prepare(self::SCIENCELAB, 1, 2, -1);
        $freeBefore = $this->freeSupply();

        $this->assertTrue($this->service->leveldown(self::COLONY_ID, self::SCIENCELAB));

        $row = $this->row(self::SCIENCELAB);
        $this->assertSame(0, (int) $row->level);
        $this->assertNull($row->tile_x);
        $this->assertNull($row->tile_y);
        $this->assertFalse(ResourcesService::reservesFirstLevel($row));
        $this->assertSame($freeBefore + 6, $this->freeSupply());
    }

    public function test_leveldown_from_two_to_one_keeps_the_tile(): void
    {
        $this->prepare(self::INFIRMARY, 2, 2, -1);

        $this->assertTrue($this->service->leveldown(self::COLONY_ID, self::INFIRMARY));

        $row = $this->row(self::INFIRMARY);
        $this->assertSame(1, (int) $row->level);
        $this->assertSame(2, (int) $row->tile_x);
        $this->assertSame(-1, (int) $row->tile_y);
    }

    public function test_freshly_placed_level_zero_building_still_reserves(): void
    {
        $this->prepare(self::SCIENCELAB, 0, 2, -1);

        $row = $this->row(self::SCIENCELAB);
        $this->assertTrue(ResourcesService::reservesFirstLevel($row));
        $this->assertSame(6, $this->app->make(ResourcesService::class)->buildingWorkplaces(self::COLONY_ID)['reserved']);
    }

    public function test_harvester_leveldown_to_zero_clears_tile_and_transit_state(): void
    {
        $this->prepare(self::HARVESTER, 1, 1, 0);
        DB::table('colony_buildings')
            ->where('colony_id', self::COLONY_ID)
            ->where('building_id', self::HARVESTER)
            ->update(['pending_until_tick' => 99999, 'instability_outage_until_tick' => 99999]);

        $this->assertTrue($this->service->leveldown(self::COLONY_ID, self::HARVESTER));

        $row = $this->row(self::HARVESTER);
        $this->assertSame(0, (int) $row->level);
        $this->assertNull($row->tile_x);
        $this->assertNull($row->pending_until_tick);
        $this->assertNull($row->instability_outage_until_tick);
    }

    // ── Bauabbruch (Owner decision 2026-09-24): a placed level-0 construction site ──

    public function test_cancelling_a_placed_construction_site_releases_tile_and_reserve(): void
    {
        $this->prepare(self::SCIENCELAB, 0, 2, -1);
        DB::table('colony_buildings')->where('colony_id', self::COLONY_ID)->where('building_id', self::SCIENCELAB)->update(['ap_spend' => 3]);
        $freeBefore = $this->freeSupply();

        $this->assertNull($this->service->leveldownBlocker(self::COLONY_ID, self::SCIENCELAB));
        $this->assertTrue($this->service->leveldown(self::COLONY_ID, self::SCIENCELAB));

        $row = $this->row(self::SCIENCELAB);
        $this->assertNotNull($row, 'the row stays, it is only no longer placed');
        $this->assertSame(0, (int) $row->level, 'the level stays 0');
        $this->assertNull($row->tile_x);
        $this->assertNull($row->tile_y);
        $this->assertSame(0, (int) $row->ap_spend, 'AP invested into the construction site are forfeited');
        $this->assertFalse(ResourcesService::reservesFirstLevel($row));
        $this->assertSame($freeBefore + 6, $this->freeSupply());
    }

    public function test_cancelling_a_harvester_construction_site_clears_transit_state(): void
    {
        $this->prepare(self::HARVESTER, 0, 1, 0);
        DB::table('colony_buildings')
            ->where('colony_id', self::COLONY_ID)
            ->where('building_id', self::HARVESTER)
            ->update(['pending_until_tick' => 99999, 'instability_outage_until_tick' => 99999]);

        $this->assertTrue($this->service->leveldown(self::COLONY_ID, self::HARVESTER));

        $row = $this->row(self::HARVESTER);
        $this->assertSame(0, (int) $row->level);
        $this->assertNull($row->tile_x);
        $this->assertNull($row->pending_until_tick);
        $this->assertNull($row->instability_outage_until_tick);
    }

    public function test_unplaced_level_zero_building_cannot_be_levelled_down(): void
    {
        $this->prepare(self::SCIENCELAB, 0, null, null);

        $this->assertSame('not_placed', $this->service->leveldownBlocker(self::COLONY_ID, self::SCIENCELAB));
        $this->assertFalse($this->service->leveldown(self::COLONY_ID, self::SCIENCELAB));
        $this->assertSame(0, (int) $this->row(self::SCIENCELAB)->level);
    }

    public function test_command_center_cannot_be_levelled_down_to_zero(): void
    {
        $this->prepare(self::CC, 1, null, null);

        $this->assertFalse($this->service->checkLevelDownLimit(self::COLONY_ID, self::CC));
        $this->assertFalse($this->service->leveldown(self::COLONY_ID, self::CC));
        $this->assertSame(1, (int) $this->row(self::CC)->level);
    }

    public function test_command_center_can_still_be_levelled_down_above_one(): void
    {
        $this->prepare(self::CC, 2, null, null);

        $this->assertTrue($this->service->leveldown(self::COLONY_ID, self::CC));
        $this->assertSame(1, (int) $this->row(self::CC)->level);
    }
}
