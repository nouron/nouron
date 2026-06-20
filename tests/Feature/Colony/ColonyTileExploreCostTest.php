<?php

namespace Tests\Feature\Colony;

use App\Services\ColonyTileService;
use App\Services\Techtree\PersonellService;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Ring-staggered Navigation-AP cost for exploring fogged tiles
 * (config('game.colony.explore_cost_per_ring')).
 *
 * Fixture: Colony 1 (Springfield), user_id=3 (Bart).
 */
class ColonyTileExploreCostTest extends TestCase
{
    use RefreshDatabase;

    private const COLONY_ID = 1;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(TestSeeder::class)->run();

        // Tests bypass all game checks by default (phpunit.xml). This suite is
        // exactly about the Nav-AP gate, so enable it explicitly.
        config(['game.bypass.ap_checks' => false]);
    }

    private function fogTile(int $q, int $r, int $ring, string $type = 'terrain_empty'): void
    {
        DB::table('colony_tiles')->updateOrInsert(
            ['colony_id' => self::COLONY_ID, 'q' => $q, 'r' => $r],
            ['ring' => $ring, 'tile_type' => $type, 'is_explored' => 0, 'is_colony_zone' => 0, 'is_deep_scanned' => 0]
        );
    }

    private function navAp(): int
    {
        return $this->app->make(PersonellService::class)
            ->getAvailableActionPoints('navigation', self::COLONY_ID);
    }

    public function test_exploring_ring1_tile_costs_1_nav_ap(): void
    {
        $this->fogTile(1, -1, 1);
        $before = $this->navAp();

        $result = $this->app->make(ColonyTileService::class)->exploreTile(self::COLONY_ID, 1, -1);

        $this->assertTrue($result['ok']);
        $this->assertSame($before - 1, $this->navAp());
    }

    public function test_exploring_ring2_tile_costs_2_nav_ap(): void
    {
        $this->fogTile(2, 0, 2);
        $before = $this->navAp();

        $result = $this->app->make(ColonyTileService::class)->exploreTile(self::COLONY_ID, 2, 0);

        $this->assertTrue($result['ok']);
        $this->assertSame($before - 2, $this->navAp());
    }

    public function test_exploring_ring3_tile_costs_3_nav_ap(): void
    {
        $this->fogTile(3, 0, 3);
        $before = $this->navAp();

        $result = $this->app->make(ColonyTileService::class)->exploreTile(self::COLONY_ID, 3, 0);

        $this->assertTrue($result['ok']);
        $this->assertSame($before - 3, $this->navAp());
    }

    public function test_exploring_ring2_tile_fails_when_only_1_nav_ap_left(): void
    {
        $this->fogTile(2, 0, 2);

        $personell = $this->app->make(PersonellService::class);
        $available = $personell->getAvailableActionPoints('navigation', self::COLONY_ID);
        $personell->lockActionPoints('navigation', self::COLONY_ID, $available - 1);

        $this->assertSame(1, $this->navAp(), 'precondition: exactly 1 Nav-AP left');

        $result = $this->app->make(ColonyTileService::class)->exploreTile(self::COLONY_ID, 2, 0);

        $this->assertFalse($result['ok']);
        $this->assertSame(__('colony.error_no_nav_ap'), $result['error']);
        $this->assertSame(0, (int) DB::table('colony_tiles')
            ->where('colony_id', self::COLONY_ID)
            ->where('q', 2)->where('r', 0)
            ->value('is_explored'));
    }
}
