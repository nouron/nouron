<?php

namespace Tests\Feature\Colony;

use App\Models\ColonyTile;
use App\Services\AdvisorService;
use App\Services\ColonyTileService;
use App\Services\HangarService;
use App\Services\TickService;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/** T30 §13.4: exactly one action plus one reason per find tile, read by guards, UI and bot. */
class FindActionTest extends TestCase
{
    use RefreshDatabase;

    private const COLONY_ID = 1;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(TestSeeder::class)->run();
        $this->app->instance(TickService::class, new TickService(100));
        DB::table('colony_hangar_missions')->where('colony_id', self::COLONY_ID)->delete();
        DB::table('colony_ships')->where('colony_id', self::COLONY_ID)->delete();
        DB::table('colony_buildings')->where('colony_id', self::COLONY_ID)->where('building_id', 44)->delete();
        config(['game.bypass.ap_checks' => false]);
    }

    private function hall(int $level): void
    {
        DB::table('colony_buildings')->updateOrInsert(
            ['colony_id' => self::COLONY_ID, 'building_id' => 44, 'instance_id' => 1],
            ['level' => $level, 'status_points' => 20, 'ap_spend' => 0, 'tile_x' => $level > 0 ? 5 : null, 'tile_y' => $level > 0 ? 5 : null]
        );
    }

    private function ship(int $shipId, int $bay, string $state = 'docked', float $sp = 20): void
    {
        DB::table('colony_ships')->insert(['colony_id' => self::COLONY_ID, 'ship_id' => $shipId, 'bay' => $bay,
            'ship_state' => $state, 'level' => 0, 'status_points' => $sp, 'ap_spend' => 0, 'deliver_at_tick' => $state === 'building' ? 103 : null]);
    }

    private function find(string $type, bool $scanned = true): ColonyTile
    {
        DB::table('colony_tiles')->updateOrInsert(
            ['colony_id' => self::COLONY_ID, 'q' => 2, 'r' => 0],
            ['ring' => 2, 'tile_type' => 'terrain_empty', 'is_explored' => 1, 'is_colony_zone' => 0,
                'is_deep_scanned' => $scanned ? 1 : 0, 'event_type' => $type]
        );

        return ColonyTile::where('colony_id', self::COLONY_ID)->where('q', 2)->where('r', 0)->firstOrFail();
    }

    private function action(ColonyTile $tile): array
    {
        return $this->app->make(ColonyTileService::class)->findAction(self::COLONY_ID, $tile);
    }

    private function assertAction(string $action, string $reason, ColonyTile $tile): array
    {
        $result = $this->action($tile);
        $this->assertSame([$action, $reason], [$result['action'], $result['reason']]);

        return $result;
    }

    public function test_unscanned_and_false_alarm(): void
    {
        $this->assertAction('scan', 'unscanned', $this->find('find_large', scanned: false));
        $this->assertAction('none', 'false', $this->find('find_false'));
    }

    public function test_without_hangar_or_on_level_zero_self_salvage_for_every_size(): void
    {
        $this->assertAction('self_salvage', 'self', $this->find('find_large'));
        $this->hall(0);
        $this->assertAction('self_salvage', 'self', $this->find('find_large'));
    }

    public function test_flight_with_the_gift_drone_carries_costs_and_yield(): void
    {
        $this->hall(1);
        $this->ship(85, 0);

        $r = $this->assertAction('flight', 'flight', $this->find('find_medium'));

        $this->assertSame([0, 'drone'], [$r['bay'], $r['ship_key']]);
        $this->assertSame(['base' => 10, 'total' => 10], $r['yield']);
        $this->assertSame([1, 1, 102], [$r['costs']['nav_ap'], $r['costs']['organika'], $r['costs']['return_sol']]);
    }

    public function test_smallest_carrying_class_flies(): void
    {
        $this->hall(2);
        $this->ship(85, 0);
        $this->ship(47, 2);
        $this->assertSame('drone', $this->action($this->find('find_small'))['ship_key']);
        $this->assertSame(['freighter', 2], [$this->action($this->find('find_large'))['ship_key'], $this->action($this->find('find_large'))['bay']]);
    }

    public function test_waiting_reasons(): void
    {
        $this->hall(1);
        $this->ship(85, 0, 'dispatched');
        DB::table('colony_hangar_missions')->insert(['colony_id' => self::COLONY_ID, 'bay' => 0, 'ship_id' => 85,
            'destination' => 'mission_recon_flight', 'sol_distance' => 1, 'dispatch_tick' => 100, 'state' => 'active']);
        $this->assertSame(102, $this->assertAction('wait', 'ship_busy', $this->find('find_small'))['return_sol']);

        // A docked carrier is always ready: salvage flights do not wear ships.
        DB::table('colony_ships')->where('colony_id', self::COLONY_ID)->update(['ship_state' => 'docked', 'status_points' => 1]);
        $this->assertAction('flight', 'flight', $this->find('find_small'));
    }

    public function test_locked_reasons_for_large_finds(): void
    {
        $this->hall(1);
        $this->ship(85, 0);
        $this->assertSame(2, $this->assertAction('locked', 'needs_level', $this->find('find_large'))['needs_level']);

        $this->hall(2);
        $this->assertAction('locked', 'needs_freighter', $this->find('find_large'));

        $this->ship(47, 3); // bay 3 closed at level 2
        $this->assertAction('locked', 'ship_inactive', $this->find('find_large'));
    }

    public function test_in_flight_wins_over_everything_but_scan(): void
    {
        $this->hall(1);
        $this->ship(85, 0, 'dispatched');
        DB::table('colony_hangar_missions')->insert(['colony_id' => self::COLONY_ID, 'bay' => 0, 'ship_id' => 85,
            'destination' => 'mission_find_salvage', 'sol_distance' => 1, 'dispatch_tick' => 100, 'state' => 'active', 'target' => '{"q":2,"r":0}']);

        $this->assertSame(102, $this->assertAction('none', 'in_flight', $this->find('find_small'))['return_sol']);
    }

    public function test_salvage_guard_follows_find_action(): void
    {
        $svc = fn () => $this->app->make(ColonyTileService::class);
        $this->hall(1);
        $this->ship(85, 0);
        $this->find('find_small');
        $ap = $this->app->make(AdvisorService::class)->getAvailableActionPoints(self::COLONY_ID);

        $this->assertSame('salvage_by_flight', $svc()->salvageFind(self::COLONY_ID, 2, 0)['error']);
        $this->assertSame($ap, $this->app->make(AdvisorService::class)->getAvailableActionPoints(self::COLONY_ID));

        $this->find('find_small');
        DB::table('colony_buildings')->where('colony_id', self::COLONY_ID)->where('building_id', 44)->update(['level' => 0, 'tile_x' => null, 'tile_y' => null]);
        $this->assertTrue($svc()->salvageFind(self::COLONY_ID, 2, 0)['ok'], 'demolished hangar: self-salvage is back');
    }

    public function test_self_salvage_is_blocked_while_a_flight_is_out_even_without_hangar(): void
    {
        $this->find('find_small');
        DB::table('colony_hangar_missions')->insert(['colony_id' => self::COLONY_ID, 'bay' => 0, 'ship_id' => 85,
            'destination' => 'mission_find_salvage', 'sol_distance' => 1, 'dispatch_tick' => 100, 'state' => 'active', 'target' => '{"q":2,"r":0}']);

        $this->assertSame('find_in_flight', $this->app->make(ColonyTileService::class)->salvageFind(self::COLONY_ID, 2, 0)['error']);
    }

    public function test_flight_reason_matches_an_accepted_dispatch(): void
    {
        $this->hall(1);
        $this->ship(85, 0);
        $r = $this->action($this->find('find_medium'));

        $this->app->make(HangarService::class)->dispatchShip(self::COLONY_ID, $r['bay'], 'mission_find_salvage', ['q' => 2, 'r' => 0], 'normal');

        $this->assertSame('in_flight', $this->action($this->find('find_medium'))['reason']);
    }

    public function test_tile_payload_carries_action_and_translated_reason(): void
    {
        $this->hall(1);
        $this->ship(85, 0);
        $this->find('find_medium');

        $tile = $this->app->make(ColonyTileService::class)->getTilesForColony(self::COLONY_ID)->firstWhere(fn ($t) => $t['q'] === 2 && $t['r'] === 0);

        $this->assertSame('flight', $tile['find']['action']['action']);
        $this->assertStringContainsString('10', $tile['find']['action']['text']);
    }
}
