<?php

namespace Tests\Feature\Colony;

use App\Services\AdvisorService;
use App\Services\ColonyTileService;
use App\Services\TickService;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * AP scale spec 2026-11-13 §4/§6 (rule §5.1.2: small costs get frequency, not a discount):
 * cartography from knowledge.cartography.free_explore_from_level makes the first exploration
 * of a Sol free; an Uplink station from game.finds.free_scan_uplink_level makes the first
 * deep scan of a Sol free.
 */
class FreeDailyNavActionTest extends TestCase
{
    use RefreshDatabase;

    private const COLONY_ID = 1;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(TestSeeder::class)->run();
        $this->tickAt(10);
        DB::table('colony_daily_ap')->where('colony_id', self::COLONY_ID)->delete();
        config([
            'game.bypass.ap_checks' => false,
            'game.colony.explore_cost_per_ring' => [1 => 1, 2 => 1, 3 => 2],
            'knowledge.cartography.free_explore_from_level' => 4,
            'game.finds.scan_ap' => 1,
            'game.finds.free_scan_uplink_level' => 2,
        ]);
    }

    private function tickAt(int $tick): void
    {
        $this->app->instance(TickService::class, new TickService($tick));
    }

    private function tiles(): ColonyTileService
    {
        return $this->app->make(ColonyTileService::class);
    }

    private function ap(): int
    {
        return $this->app->make(AdvisorService::class)->getAvailableActionPoints(self::COLONY_ID);
    }

    private function fogTile(int $q, int $r, int $ring): void
    {
        DB::table('colony_tiles')->updateOrInsert(
            ['colony_id' => self::COLONY_ID, 'q' => $q, 'r' => $r],
            ['ring' => $ring, 'tile_type' => 'terrain_empty', 'is_explored' => 0, 'is_colony_zone' => 0, 'is_deep_scanned' => 0, 'event_type' => null]
        );
    }

    private function signalTile(int $q, int $r): void
    {
        DB::table('colony_tiles')->updateOrInsert(
            ['colony_id' => self::COLONY_ID, 'q' => $q, 'r' => $r],
            ['ring' => 2, 'tile_type' => 'terrain_empty', 'is_explored' => 1, 'is_colony_zone' => 0, 'is_deep_scanned' => 0, 'event_type' => 'find_small']
        );
    }

    private function setCartography(int $level): void
    {
        DB::table('colony_researches')->updateOrInsert(
            ['colony_id' => self::COLONY_ID, 'research_id' => (int) config('knowledge.cartography.id')],
            ['level' => $level, 'ap_spend' => 0, 'status_points' => 20]
        );
    }

    private function setUplink(int $level): void
    {
        DB::table('colony_buildings')->updateOrInsert(
            ['colony_id' => self::COLONY_ID, 'building_id' => (int) config('buildings.uplinkStation.id', 54), 'instance_id' => 1],
            ['level' => $level, 'status_points' => 20, 'ap_spend' => 0]
        );
    }

    public function test_first_exploration_of_a_sol_is_free_from_cartography_level(): void
    {
        $this->setCartography(4);
        $this->fogTile(3, 0, 3);
        $this->fogTile(0, 3, 3);
        $before = $this->ap();

        $this->assertSame(0, $this->tiles()->exploreCost(self::COLONY_ID, 3));
        $this->assertTrue($this->tiles()->exploreTile(self::COLONY_ID, 3, 0)['ok']);
        $this->assertSame($before, $this->ap());

        $this->assertSame(2, $this->tiles()->exploreCost(self::COLONY_ID, 3));
        $this->assertTrue($this->tiles()->exploreTile(self::COLONY_ID, 0, 3)['ok']);
        $this->assertSame($before - 2, $this->ap());
    }

    public function test_free_exploration_returns_next_sol(): void
    {
        $this->setCartography(5);
        $this->fogTile(3, 0, 3);
        $this->tiles()->exploreTile(self::COLONY_ID, 3, 0);

        $this->tickAt(11);

        $this->assertSame(0, $this->tiles()->exploreCost(self::COLONY_ID, 2));
    }

    public function test_no_free_exploration_below_the_level(): void
    {
        $this->setCartography(3);

        $this->assertSame(2, $this->tiles()->exploreCost(self::COLONY_ID, 3));
    }

    public function test_first_deep_scan_of_a_sol_is_free_with_uplink(): void
    {
        $this->setUplink(2);
        $this->signalTile(2, 0);
        $this->signalTile(0, 2);
        $before = $this->ap();

        $this->assertSame(0, $this->tiles()->deepScanCost(self::COLONY_ID));
        $this->assertTrue($this->tiles()->deepScanTile(self::COLONY_ID, 2, 0)['ok']);
        $this->assertSame($before, $this->ap());

        $this->assertSame(1, $this->tiles()->deepScanCost(self::COLONY_ID));
        $this->assertTrue($this->tiles()->deepScanTile(self::COLONY_ID, 0, 2)['ok']);
        $this->assertSame($before - 1, $this->ap());
    }

    public function test_deep_scan_costs_full_without_uplink_level(): void
    {
        $this->setUplink(1);

        $this->assertSame(1, $this->tiles()->deepScanCost(self::COLONY_ID));
    }
}
