<?php

namespace Tests\Feature\Colony;

/**
 * Lenn's "Navigation-Rabatt" Vier-Ausgänge-Pool outcome (A42, GDD §12 "Deva &
 * Lenn — taktische Information") — the next exploreTile() call costs 0
 * Navigation-AP, consumed the moment it applies.
 */

use App\Services\AdvisorService;
use App\Services\ColonyTileService;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ColonyTileExploreNavVoucherTest extends TestCase
{
    use RefreshDatabase;

    private const COLONY_ID = 1;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(TestSeeder::class)->run();
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
        return $this->app->make(AdvisorService::class)->getAvailableActionPoints(self::COLONY_ID);
    }

    private function setActiveNavVoucher(bool $active): void
    {
        DB::table('colony_information_pool_state')->updateOrInsert(
            ['colony_id' => self::COLONY_ID],
            ['active_nav_voucher' => $active]
        );
    }

    public function test_active_voucher_makes_the_next_exploration_free(): void
    {
        $this->fogTile(2, 0, 2);
        $this->setActiveNavVoucher(true);
        $before = $this->navAp();

        $result = $this->app->make(ColonyTileService::class)->exploreTile(self::COLONY_ID, 2, 0);

        $this->assertTrue($result['ok']);
        $this->assertSame($before, $this->navAp(), 'the voucher must fully waive the Nav-AP cost');
    }

    public function test_active_voucher_is_consumed_after_use(): void
    {
        $this->fogTile(2, 0, 2);
        $this->fogTile(3, 0, 3);
        $this->setActiveNavVoucher(true);

        $this->app->make(ColonyTileService::class)->exploreTile(self::COLONY_ID, 2, 0);

        $state = DB::table('colony_information_pool_state')->where('colony_id', self::COLONY_ID)->first();
        $this->assertFalse((bool) $state->active_nav_voucher);

        $before = $this->navAp();
        $result = $this->app->make(ColonyTileService::class)->exploreTile(self::COLONY_ID, 3, 0);

        $this->assertTrue($result['ok']);
        $this->assertSame($before - 3, $this->navAp(), 'the second exploration must be charged full price — voucher already spent');
    }

    public function test_exploration_without_active_voucher_charges_normally(): void
    {
        $this->fogTile(1, -1, 1);
        $before = $this->navAp();

        $result = $this->app->make(ColonyTileService::class)->exploreTile(self::COLONY_ID, 1, -1);

        $this->assertTrue($result['ok']);
        $this->assertSame($before - 1, $this->navAp());
    }
}
