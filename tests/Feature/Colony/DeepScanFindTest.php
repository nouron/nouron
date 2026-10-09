<?php

namespace Tests\Feature\Colony;

use App\Services\AdvisorService;
use App\Services\ColonyTileService;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/** T30 Pool v1: deep-scan costs from config, find info only after the scan. */
class DeepScanFindTest extends TestCase
{
    use RefreshDatabase;

    private const COLONY_ID = 1;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(TestSeeder::class)->run();
        config(['game.bypass.ap_checks' => false]);
    }

    private function signalTile(?string $eventType, bool $explored = true): void
    {
        DB::table('colony_tiles')->updateOrInsert(
            ['colony_id' => self::COLONY_ID, 'q' => 2, 'r' => 0],
            ['ring' => 2, 'tile_type' => 'terrain_empty', 'is_explored' => $explored ? 1 : 0,
                'is_colony_zone' => 0, 'is_deep_scanned' => 0, 'event_type' => $eventType]
        );
    }

    private function navAp(): int
    {
        return $this->app->make(AdvisorService::class)->getAvailableActionPoints(self::COLONY_ID);
    }

    private function svc(): ColonyTileService
    {
        return $this->app->make(ColonyTileService::class);
    }

    public function test_scan_reveals_find_and_locks_2_ap(): void
    {
        $this->signalTile('find_medium');
        $before = $this->navAp();

        $res = $this->svc()->deepScanTile(self::COLONY_ID, 2, 0);

        $this->assertTrue($res['ok']);
        $this->assertSame(
            ['type' => 'find_medium', 'rg' => 10, 'ap_total' => 16, 'ap_spent' => 0, 'ap_cap_per_sol' => 4, 'false' => false],
            $res['tile']['find']
        );
        $this->assertSame($before - 2, $this->navAp());
    }

    public function test_uplink_lv2_scan_costs_1_ap(): void
    {
        DB::table('colony_buildings')->updateOrInsert(
            ['colony_id' => self::COLONY_ID, 'building_id' => 54, 'instance_id' => 1],
            ['level' => 2, 'status_points' => 20, 'ap_spend' => 0, 'tile_x' => 3, 'tile_y' => 0]
        );
        $this->signalTile('find_small');
        $before = $this->navAp();

        $this->assertTrue($this->svc()->deepScanTile(self::COLONY_ID, 2, 0)['ok']);
        $this->assertSame($before - 1, $this->navAp());
    }

    public function test_unscanned_explored_tile_hides_find(): void
    {
        $this->signalTile('find_large', false);

        $res = $this->svc()->exploreTile(self::COLONY_ID, 2, 0);

        $this->assertTrue($res['ok']);
        $this->assertNull($res['tile']['find']);
        $this->assertNull($res['tile']['event_type']);
        $this->assertTrue($res['tile']['has_signal']);
    }

    public function test_false_find_has_zero_rg(): void
    {
        $this->signalTile('find_false');

        $find = $this->svc()->deepScanTile(self::COLONY_ID, 2, 0)['tile']['find'];

        $this->assertTrue($find['false']);
        $this->assertSame(0, $find['rg']);
    }

    public function test_scan_without_signal_errors_and_locks_nothing(): void
    {
        $this->signalTile(null);
        $before = $this->navAp();

        $res = $this->svc()->deepScanTile(self::COLONY_ID, 2, 0);

        $this->assertSame('no_signal', $res['error']);
        $this->assertSame($before, $this->navAp());
    }
}
