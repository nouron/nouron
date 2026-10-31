<?php

namespace Tests\Feature\Hangar;

use App\Exceptions\GameRuleException;
use App\Models\User;
use App\Services\AdvisorService;
use App\Services\FindFlightService;
use App\Services\HangarService;
use App\Services\RunTaskCatalog;
use App\Services\TickService;
use App\Support\HangarBays;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\CreatesForeignColony;
use Tests\TestCase;

/** T30 §13.3: salvage flight to a scanned find — distance from ring, no roll, flat 1 AP, no wear, yield = find Rg. */
class FindFlightTest extends TestCase
{
    use CreatesForeignColony;
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
        config(['game.bypass.ap_checks' => false, 'game.bypass.resource_costs' => false]);
        DB::table('colony_hangar_missions')->where('colony_id', self::COLONY_ID)->delete();
        DB::table('colony_ships')->where('colony_id', self::COLONY_ID)->delete();
        DB::table('colony_buildings')->where('colony_id', self::COLONY_ID)->where('building_id', 44)->delete();
        DB::table('colony_resources')->where('colony_id', self::COLONY_ID)->where('resource_id', 5)->update(['amount' => 50]);
        $this->hall(3);
    }

    private function hall(int $level): void
    {
        DB::table('colony_buildings')->updateOrInsert(
            ['colony_id' => self::COLONY_ID, 'building_id' => 44, 'instance_id' => 1],
            ['level' => $level, 'status_points' => 20, 'ap_spend' => 0, 'tile_x' => $level > 0 ? 5 : null, 'tile_y' => $level > 0 ? 5 : null]
        );
    }

    private function ship(int $shipId, int $bay, float $sp = 20): void
    {
        DB::table('colony_ships')->insert([
            'colony_id' => self::COLONY_ID, 'ship_id' => $shipId, 'bay' => $bay,
            'ship_state' => 'docked', 'level' => 0, 'status_points' => $sp, 'ap_spend' => 0,
        ]);
    }

    private function find(int $q, int $ring, string $type, bool $scanned = true): void
    {
        DB::table('colony_tiles')->updateOrInsert(
            ['colony_id' => self::COLONY_ID, 'q' => $q, 'r' => 0],
            ['ring' => $ring, 'tile_type' => 'terrain_empty', 'is_explored' => 1, 'is_colony_zone' => 0,
                'is_deep_scanned' => $scanned ? 1 : 0, 'event_type' => $type]
        );
    }

    private function fly(int $bay, int $q): void
    {
        $this->app->make(HangarService::class)->dispatchShip(self::COLONY_ID, $bay, 'mission_find_salvage', ['q' => $q, 'r' => 0], 'normal');
    }

    private function assertRejected(string $code, callable $fn): void
    {
        try {
            $fn();
            $this->fail("expected {$code}");
        } catch (GameRuleException $e) {
            $this->assertSame($code, $e->errorCode);
        }
    }

    private function ap(): int
    {
        return $this->app->make(AdvisorService::class)->getAvailableActionPoints(self::COLONY_ID);
    }

    private function organics(): int
    {
        return (int) DB::table('colony_resources')->where('colony_id', self::COLONY_ID)->where('resource_id', 5)->value('amount');
    }

    private function regolith(): int
    {
        return (int) DB::table('colony_resources')->where('colony_id', self::COLONY_ID)->where('resource_id', 3)->value('amount');
    }

    public function test_distance_costs_and_yield_come_from_config(): void
    {
        $svc = $this->app->make(FindFlightService::class);
        $this->assertSame([1, 2], [$svc->solDistance(2), $svc->solDistance(3)]);
        $this->assertSame(['base' => 10, 'total' => 10], $svc->findFlightYield(self::COLONY_ID, 'find_medium'));
        $this->assertSame(18, $svc->findFlightYield(self::COLONY_ID, 'find_large')['total']);
        $ring2 = $svc->costs(self::COLONY_ID, 2);
        $this->assertSame([1, 1, 1, 2], [$ring2['sol_distance'], $ring2['ap_cost'], $ring2['organika'], $ring2['duration']]);
        $ring3 = $svc->costs(self::COLONY_ID, 3);
        $this->assertSame([2, 1, 2, 4], [$ring3['sol_distance'], $ring3['ap_cost'], $ring3['organika'], $ring3['duration']]);
    }

    public function test_dispatch_charges_ring_costs_and_stores_distance(): void
    {
        $this->ship(self::DRONE, 0);
        $this->find(3, 3, 'find_medium');
        [$ap, $or] = [$this->ap(), $this->organics()];

        $this->fly(0, 3);

        $cost = $this->app->make(FindFlightService::class)->costs(self::COLONY_ID, 3);
        $this->assertSame($ap - $cost['ap_cost'], $this->ap());
        $this->assertSame($or - $cost['organika'], $this->organics());
        $mission = DB::table('colony_hangar_missions')->where('colony_id', self::COLONY_ID)->first();
        $this->assertSame([2, '{"q":3,"r":0}', 0], [(int) $mission->sol_distance, $mission->target, (int) $mission->bay]);
    }

    public function test_target_rules(): void
    {
        $this->ship(self::DRONE, 0);
        $this->ship(self::CORVETTE, 3);
        $this->find(2, 2, 'find_small', scanned: false);
        $this->assertRejected('find_not_scanned', fn () => $this->fly(0, 2));
        $this->find(2, 2, 'find_false');
        $this->assertRejected('invalid_target', fn () => $this->fly(0, 2));
        $this->find(2, 2, 'find_large');
        $this->assertRejected('find_too_large', fn () => $this->fly(0, 2));
        $this->find(2, 2, 'find_small');
        $this->find(2, 2, 'find_small');
        $this->assertRejected('wrong_ship_type', fn () => $this->fly(3, 2)); // corvette carries nothing
    }

    public function test_second_dispatch_to_the_same_find_is_rejected_without_cost(): void
    {
        $this->ship(self::DRONE, 0);
        $this->ship(self::FREIGHTER, 2);
        $this->find(2, 2, 'find_small');
        $this->fly(0, 2);
        [$ap, $or] = [$this->ap(), $this->organics()];

        $this->assertRejected('find_in_flight', fn () => $this->fly(2, 2));
        $this->assertSame([$ap, $or], [$this->ap(), $this->organics()]);
    }

    public function test_dispatch_to_a_foreign_colony_find_is_invalid(): void
    {
        $foreign = $this->createForeignColony([25 => 1]);
        DB::table('colony_tiles')->insert(['colony_id' => $foreign['colony_id'], 'q' => 9, 'r' => 9, 'ring' => 2,
            'tile_type' => 'terrain_empty', 'is_explored' => 1, 'is_colony_zone' => 0, 'is_deep_scanned' => 1, 'event_type' => 'find_small']);
        $this->ship(self::DRONE, 0);

        $this->assertRejected('invalid_target', fn () => $this->app->make(HangarService::class)
            ->dispatchShip(self::COLONY_ID, 0, 'mission_find_salvage', ['q' => 9, 'r' => 9], 'normal'));
        $this->assertSame('find_small', DB::table('colony_tiles')->where('colony_id', $foreign['colony_id'])->value('event_type'));
    }

    public function test_return_pays_the_find_rg_without_roll_and_frees_the_tile(): void
    {
        config(['game.missions.difficulty.base_chance' => ['easy' => 0.0, 'normal' => 0.0, 'hard' => 0.0]]);
        $this->ship(self::DRONE, 0);
        $this->find(2, 2, 'find_medium');
        $rg = $this->regolith();
        $this->fly(0, 2);

        Artisan::call('game:tick', ['--run' => 1, '--tick' => 102]);

        $this->assertSame($rg + 10, $this->regolith());
        $tile = DB::table('colony_tiles')->where('colony_id', self::COLONY_ID)->where('q', 2)->where('r', 0)->first();
        $this->assertSame([null, 0, 0], [$tile->event_type, (int) $tile->is_deep_scanned, 0]);
        $mission = DB::table('colony_hangar_missions')->where('colony_id', self::COLONY_ID)->first();
        $this->assertSame(['completed', 1, 'normal'], [$mission->state, (int) $mission->succeeded, $mission->difficulty]);
        $this->assertContains('normal', app(RunTaskCatalog::class)->countedDifficulties(), 'E9: counts for task_expedition_coverage');
        $rewards = json_decode(DB::table('colony_log')->where('event', 'hangar.mission_completed')->where('tick', 102)->value('parameters'), true)['rewards'];
        $this->assertSame(['find_type' => 'find_medium', 'find_regolith' => 10], $rewards);
    }

    public function test_salvage_flight_does_not_wear_the_ship_and_needs_no_repair(): void
    {
        $this->ship(self::DRONE, 0, 1.0);   // far below any old return guarantee
        $this->find(3, 3, 'find_medium');
        $rg = $this->regolith();
        $this->fly(0, 3);
        foreach ([101, 102, 103, 104] as $tick) {
            Artisan::call('game:tick', ['--run' => 1, '--tick' => $tick]);
        }

        $this->assertSame(1.0, (float) DB::table('colony_ships')->where('colony_id', self::COLONY_ID)->where('bay', 0)->value('status_points'));
        $this->assertSame('docked', DB::table('colony_ships')->where('colony_id', self::COLONY_ID)->where('bay', 0)->value('ship_state'));
        $this->assertSame($rg + 10, $this->regolith());
    }

    public function test_flight_still_pays_after_the_hall_was_demolished(): void
    {
        $this->ship(self::DRONE, 0);
        $this->find(2, 2, 'find_small');
        $rg = $this->regolith();
        $this->fly(0, 2);
        $this->hall(0);

        Artisan::call('game:tick', ['--run' => 1, '--tick' => 102]);

        $this->assertSame($rg + 4, $this->regolith());
    }

    public function test_flight_to_a_vanished_find_is_not_succeeded(): void
    {
        $this->ship(self::DRONE, 0);
        $this->find(2, 2, 'find_small');
        $rg = $this->regolith();
        $this->fly(0, 2);
        DB::table('colony_tiles')->where('colony_id', self::COLONY_ID)->where('q', 2)->where('r', 0)->update(['event_type' => null]);

        Artisan::call('game:tick', ['--run' => 1, '--tick' => 102]);

        $this->assertSame($rg, $this->regolith());
        $mission = DB::table('colony_hangar_missions')->where('colony_id', self::COLONY_ID)->first();
        $this->assertSame(['completed', 0], [$mission->state, (int) $mission->succeeded], 'zero yield must not count for task_expedition_coverage');
        $event = DB::table('colony_log')->where('event', 'hangar.mission_failed')->orderByDesc('id')->first();
        $this->assertNotNull($event, 'a vanished find logs hangar.mission_failed');
        $this->assertTrue(json_decode($event->parameters, true)['find_missing'], 'event carries find_missing');
    }

    public function test_recall_frees_the_target_without_yield(): void
    {
        $this->ship(self::DRONE, 0);
        $this->find(2, 2, 'find_small');
        $rg = $this->regolith();
        $this->fly(0, 2);
        $this->app->make(HangarService::class)->recallShip(self::COLONY_ID, 0);
        $this->assertSame($rg, $this->regolith());
        $this->fly(0, 2); // target free again
    }

    public function test_catalog_lists_scanned_finds_with_costs_carry_and_yield_and_no_roll(): void
    {
        $this->ship(self::DRONE, 0);
        $this->find(2, 2, 'find_small');
        $this->find(3, 3, 'find_large');
        $this->find(4, 3, 'find_medium', scanned: false);

        $entry = collect($this->app->make(HangarService::class)->getMissionCatalogFor(self::COLONY_ID))->firstWhere('key', 'mission_find_salvage');

        $this->assertFalse($entry['roll']);
        $this->assertSame([100], array_column($entry['difficulty_options'], 'chance_pct'));
        $this->assertSame([[2, 0], [3, 0]], array_map(fn ($t) => [$t['q'], $t['r']], $entry['targets']));
        $large = $entry['targets'][1];
        $this->assertSame(['drone' => false, 'freighter' => true], $large['carry']);
        $this->assertSame(18, $large['yield']['total']);
        $this->assertSame(104, $large['return_sol']);
    }

    public function test_dispatch_route_answers_with_resourcebar_sync(): void
    {
        $this->ship(self::DRONE, 0);
        $this->find(2, 2, 'find_small');

        $this->actingAs(User::where('user_id', self::USER_ID)->firstOrFail())
            ->postJson(route('colony.hangar.dispatch', ['bay' => 0]), ['mission_key' => 'mission_find_salvage', 'difficulty' => 'normal', 'target' => ['q' => 2, 'r' => 0]])
            ->assertOk()->assertJsonStructure(['ok', 'slot', 'apAvailable', 'organika']);
    }

    public function test_mission_catalog_carries_the_ring_distance_table(): void
    {
        $this->assertSame([2 => 1, 3 => 2], config('missions.catalog.mission_find_salvage.sol_distance_by_ring'));
        // Readiness per distance must cover the ring distances even if no other mission has them.
        config(['missions.catalog' => ['mission_find_salvage' => config('missions.catalog.mission_find_salvage')]]);
        $this->ship(self::DRONE, 0, 6.5);

        $slot = collect($this->app->make(HangarService::class)->getHangarSlots(self::COLONY_ID))->firstWhere('bay', 0);

        $this->assertSame([1, 2], array_keys($slot['ship']['readiness']));
        $this->assertTrue($slot['ship']['readiness'][2]['ready']);
    }

    /** Corvette wear in the guard equals the per-Sol wear the tick applies (config/ships.php). */
    public function test_corvette_wear_mapping_matches_the_tick(): void
    {
        $distance = (int) config('missions.catalog.mission_escort_convoy.sol_distance');
        $wear = HangarBays::flightReadiness(self::CORVETTE, 20, $distance)['wear'];
        $this->assertSame(round((float) config('ships.corvette.wear_per_sol') * 2 * $distance, 2), $wear);
        $this->ship(self::CORVETTE, 3, $wear);
        $this->assertRejected('ship_too_worn', fn () => $this->app->make(HangarService::class)
            ->dispatchShip(self::COLONY_ID, 3, 'mission_escort_convoy', null, 'normal'));

        DB::table('colony_ships')->where('colony_id', self::COLONY_ID)->where('bay', 3)->update(['status_points' => $wear + 0.5]);
        $this->app->make(HangarService::class)->dispatchShip(self::COLONY_ID, 3, 'mission_escort_convoy', null, 'normal');
        foreach (range(101, 100 + 2 * $distance) as $tick) {
            Artisan::call('game:tick', ['--run' => 1, '--tick' => $tick]);
        }

        $this->assertSame(0.5, (float) DB::table('colony_ships')->where('colony_id', self::COLONY_ID)->where('bay', 3)->value('status_points'));
        $this->assertSame('completed', DB::table('colony_hangar_missions')->where('colony_id', self::COLONY_ID)->value('state'));
    }

    public function test_repair_refuses_a_dispatched_ship(): void
    {
        $this->ship(self::DRONE, 0, 10);
        $this->find(2, 2, 'find_small');
        $this->fly(0, 2);

        $this->assertRejected('no_docked_ship', fn () => $this->app->make(HangarService::class)->repairShip(self::COLONY_ID, 0));
        $this->assertSame(10.0, (float) DB::table('colony_ships')->where('colony_id', self::COLONY_ID)->where('bay', 0)->value('status_points'));
    }

    public function test_second_dispatch_of_the_same_ship_to_another_find_is_refused_without_cost(): void
    {
        $this->ship(self::DRONE, 0);
        $this->find(2, 2, 'find_small');
        $this->find(-2, 2, 'find_small');
        $this->fly(0, 2);
        [$ap, $or] = [$this->ap(), $this->organics()];

        $this->assertRejected('ship_not_docked', fn () => $this->fly(0, -2));
        $this->assertSame([$ap, $or], [$this->ap(), $this->organics()]);
        $this->assertSame(1, DB::table('colony_hangar_missions')->where('colony_id', self::COLONY_ID)->where('state', 'active')->count());
    }
}
