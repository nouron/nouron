<?php

namespace Tests\Feature\Hangar;

use App\Exceptions\GameRuleException;
use App\Models\User;
use App\Services\HangarService;
use App\Services\TickService;
use App\Support\HangarBays;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/** T30 §13.2 B1: one hangar hall, level n = ship class n and n bays, bay 0 = apron. */
class HangarBaysTest extends TestCase
{
    use RefreshDatabase;

    private const COLONY_ID = 1;

    private const USER_ID = 3;

    private const DRONE = 85;

    private const FREIGHTER = 47;

    private const CORVETTE = 37;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(TestSeeder::class)->run();
        $this->app->instance(TickService::class, new TickService(100));
        DB::table('colony_hangar_missions')->where('colony_id', self::COLONY_ID)->delete();
        DB::table('colony_ships')->where('colony_id', self::COLONY_ID)->delete();
        DB::table('colony_buildings')->where('colony_id', self::COLONY_ID)->where('building_id', 44)->delete();
        DB::table('user_resources')->where('user_id', self::USER_ID)->update(['credits' => 10000]);
    }

    private function hall(int $level, ?int $q = 5): void
    {
        DB::table('colony_buildings')->updateOrInsert(
            ['colony_id' => self::COLONY_ID, 'building_id' => 44, 'instance_id' => 1],
            ['level' => $level, 'status_points' => 20, 'ap_spend' => 0, 'tile_x' => $q, 'tile_y' => $q === null ? null : 0]
        );
    }

    private function ship(int $shipId, ?int $bay, string $state = 'docked'): int
    {
        return DB::table('colony_ships')->insertGetId([
            'colony_id' => self::COLONY_ID, 'ship_id' => $shipId, 'bay' => $bay,
            'ship_state' => $state, 'level' => 0, 'status_points' => 20, 'ap_spend' => 0,
        ]);
    }

    private function svc(): HangarService
    {
        return $this->app->make(HangarService::class);
    }

    public function test_bay_rules(): void
    {
        $this->assertFalse(HangarBays::bayOpen(0, 0));
        $this->assertTrue(HangarBays::bayOpen(0, 1));
        $this->assertTrue(HangarBays::bayOpen(1, 1));
        $this->assertFalse(HangarBays::bayOpen(2, 1));
        $this->assertTrue(HangarBays::bayOpen(3, 3));

        $this->assertFalse(HangarBays::isInactive(self::DRONE, 1, 0));
        $this->assertTrue(HangarBays::isInactive(self::DRONE, 0, 0), 'apron needs level 1');
        $this->assertTrue(HangarBays::isInactive(self::DRONE, 2, 3), 'closed bay makes even a drone inactive');
        $this->assertTrue(HangarBays::isInactive(self::FREIGHTER, 1, 1));
        $this->assertFalse(HangarBays::isInactive(self::FREIGHTER, 2, 2));
        $this->assertSame('freighter', HangarBays::shipKey(self::FREIGHTER));
    }

    public function test_hangar_is_no_longer_instanced(): void
    {
        $this->assertSame(0, (int) DB::table('buildings')->where('id', 44)->value('is_instanced'));
    }

    public function test_slots_list_apron_and_three_bays_with_open_state_per_level(): void
    {
        foreach ([1 => [true, true, false, false], 2 => [true, true, true, false], 3 => [true, true, true, true]] as $level => $open) {
            $this->hall($level);
            $slots = $this->svc()->getHangarSlots(self::COLONY_ID);
            $this->assertSame([0, 1, 2, 3], array_column($slots, 'bay'));
            $this->assertSame($open, array_column($slots, 'open'), "level {$level}");
            $this->assertSame([1, 1, 2, 3], array_column($slots, 'required_level'));
        }
    }

    public function test_no_hall_means_no_slots(): void
    {
        $this->assertSame([], $this->svc()->getHangarSlots(self::COLONY_ID));
    }

    public function test_request_lands_in_first_free_bay_never_the_apron(): void
    {
        $this->hall(2);
        $this->ship(self::DRONE, 0); // gift drone on the apron

        $this->svc()->requestShip(self::COLONY_ID, self::DRONE);
        $this->svc()->requestShip(self::COLONY_ID, self::FREIGHTER);
        $this->svc()->requestShip(self::COLONY_ID, self::DRONE); // no bay left → pending

        $bays = DB::table('colony_ships')->where('colony_id', self::COLONY_ID)->orderBy('id')->pluck('bay')->all();
        $this->assertSame([0, 1, 2, null], array_map(fn ($b) => $b === null ? null : (int) $b, $bays));
        $this->assertSame('pending', DB::table('colony_ships')->where('colony_id', self::COLONY_ID)->orderByDesc('id')->value('ship_state'));
    }

    public function test_assign_rejects_negative_bay(): void
    {
        $this->hall(1);
        $pending = $this->ship(self::DRONE, null, 'pending');

        try {
            $this->svc()->assignToHangar(self::COLONY_ID, $pending, -1);
            $this->fail('negative bay is invalid');
        } catch (GameRuleException $e) {
            $this->assertSame('bay_closed', $e->errorCode);
        }
        $this->assertNull(DB::table('colony_ships')->where('id', $pending)->value('bay'));
    }

    public function test_assign_rejects_apron_and_closed_bay(): void
    {
        $this->hall(1);
        $pending = $this->ship(self::DRONE, null, 'pending');

        try {
            $this->svc()->assignToHangar(self::COLONY_ID, $pending, 0);
            $this->fail('apron must be reserved');
        } catch (GameRuleException $e) {
            $this->assertSame('bay_reserved', $e->errorCode);
        }
        try {
            $this->svc()->assignToHangar(self::COLONY_ID, $pending, 2);
            $this->fail('bay 2 is closed at level 1');
        } catch (GameRuleException $e) {
            $this->assertSame('bay_closed', $e->errorCode);
        }

        $this->svc()->assignToHangar(self::COLONY_ID, $pending, 1);
        $this->assertSame(1, (int) DB::table('colony_ships')->where('id', $pending)->value('bay'));
    }

    public function test_ship_in_bay_3_is_inactive_at_level_2_and_active_after_levelup(): void
    {
        $this->hall(2);
        $id = $this->ship(self::DRONE, 3);
        $this->assertSame([$id], $this->svc()->inactiveShipRowIds(self::COLONY_ID));

        $this->hall(3);
        $this->assertSame([], $this->svc()->inactiveShipRowIds(self::COLONY_ID));
    }

    public function test_dispatch_recall_and_tick_resolution_run_over_the_bay(): void
    {
        config(['game.missions.difficulty.base_chance' => ['easy' => 1.0, 'normal' => 1.0, 'hard' => 1.0], 'game.missions.difficulty.chance_cap' => 1.0]);
        $this->hall(1);
        $this->ship(self::DRONE, 1);

        $this->svc()->dispatchShip(self::COLONY_ID, 1, 'mission_courier_run', null, 'normal');
        $this->assertSame(1, (int) DB::table('colony_hangar_missions')->where('colony_id', self::COLONY_ID)->value('bay'));

        Artisan::call('game:tick', ['--run' => 1, '--tick' => 102]);

        $this->assertSame('completed', DB::table('colony_hangar_missions')->where('colony_id', self::COLONY_ID)->value('state'));
        $this->assertSame('docked', DB::table('colony_ships')->where('colony_id', self::COLONY_ID)->where('bay', 1)->value('ship_state'));
    }

    public function test_a_second_hangar_hall_cannot_be_placed(): void
    {
        $this->hall(1, 5);
        DB::table('colony_tiles')->updateOrInsert(
            ['colony_id' => self::COLONY_ID, 'q' => 6, 'r' => 0],
            ['ring' => 3, 'tile_type' => 'terrain_empty', 'is_explored' => 1, 'is_colony_zone' => 1, 'is_deep_scanned' => 0, 'event_type' => null]
        );

        $res = $this->actingAs(User::where('user_id', self::USER_ID)->firstOrFail())
            ->postJson(route('colony.building.place'), ['building_id' => 44, 'q' => 6, 'r' => 0]);

        $res->assertStatus(422)->assertJson(['error' => 'building_already_placed']);
        $this->assertSame(5, (int) DB::table('colony_buildings')->where('colony_id', self::COLONY_ID)->where('building_id', 44)->value('tile_x'));
    }

    public function test_hangar_routes_take_the_bay_and_assign_validates_it(): void
    {
        $this->hall(1);
        $pending = $this->ship(self::DRONE, null, 'pending');

        $res = $this->actingAs(User::where('user_id', self::USER_ID)->firstOrFail())
            ->postJson(route('colony.hangar.assign'), ['ship_row_id' => $pending, 'bay' => 0]);

        $res->assertStatus(422)->assertJson(['error' => 'bay_reserved']);
        $this->assertStringEndsWith('/hangar/2/dispatch', route('colony.hangar.dispatch', ['bay' => 2]));
    }

    public function test_apron_ship_can_be_repaired_via_the_bay_route(): void
    {
        config(['game.bypass.ap_checks' => true]);
        $this->hall(1);
        $this->ship(self::DRONE, 0);
        DB::table('colony_ships')->where('colony_id', self::COLONY_ID)->where('bay', 0)->update(['status_points' => 10]);

        $this->actingAs(User::where('user_id', self::USER_ID)->firstOrFail())
            ->postJson(route('colony.hangar.repair', ['bay' => 0]))->assertOk();

        $this->assertSame(12.0, (float) DB::table('colony_ships')->where('colony_id', self::COLONY_ID)->where('bay', 0)->value('status_points'));
    }
}
