<?php

namespace Tests\Feature\Hangar;

use App\Exceptions\GameRuleException;
use App\Models\User;
use App\Services\AdvisorService;
use App\Services\HangarService;
use App\Services\TickService;
use App\Support\HangarBays;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/** T30 §13.x/7–8: return guarantee replaces the 25 % floor; repair per ship, AP only, values from config. */
class ShipRepairReadinessTest extends TestCase
{
    use RefreshDatabase;

    private const COLONY_ID = 1;

    private const DRONE = 85;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(TestSeeder::class)->run();
        $this->app->instance(TickService::class, new TickService(100));
        config(['game.bypass.ap_checks' => false, 'game.bypass.resource_costs' => false]);
        DB::table('colony_hangar_missions')->where('colony_id', self::COLONY_ID)->delete();
        DB::table('colony_ships')->where('colony_id', self::COLONY_ID)->delete();
        DB::table('colony_buildings')->updateOrInsert(['colony_id' => self::COLONY_ID, 'building_id' => 44, 'instance_id' => 1],
            ['level' => 1, 'status_points' => 20, 'ap_spend' => 0, 'tile_x' => 5, 'tile_y' => 5]);
        DB::table('colony_resources')->where('colony_id', self::COLONY_ID)->where('resource_id', 5)->update(['amount' => 50]);
    }

    private function drone(float $sp, int $bay = 0): void
    {
        DB::table('colony_ships')->insert(['colony_id' => self::COLONY_ID, 'ship_id' => self::DRONE, 'bay' => $bay,
            'ship_state' => 'docked', 'level' => 0, 'status_points' => $sp, 'ap_spend' => 0]);
    }

    private function ap(): int
    {
        return $this->app->make(AdvisorService::class)->getAvailableActionPoints(self::COLONY_ID);
    }

    public function test_config_replaces_constants_and_the_25_percent_floor(): void
    {
        $this->assertSame(['max_status_points' => 20, 'sp_per_click' => 2, 'ap_per_click' => 1], config('game.ship_repair'));
        $this->assertNull(config('missions.dispatch_min_sp_pct'));
        $this->assertSame(20, HangarBays::maxStatus());
    }

    public function test_readiness_rule_and_repair_clicks(): void
    {
        // Drone wear 1.5/Sol; ring 3 = 2 Sols out and back = 6.
        $this->assertSame(['ready' => false, 'wear' => 6.0, 'sp_after' => 0.0, 'repair_clicks' => 1, 'repair_ap' => 1],
            HangarBays::flightReadiness(self::DRONE, 6.0, 2));
        $this->assertFalse(HangarBays::flightReadiness(self::DRONE, 5.0, 2)['ready'], '5-6 SP used to pass the 25 % floor and abort on ring 3');
        $this->assertSame(1, HangarBays::flightReadiness(self::DRONE, 5.0, 2)['repair_clicks']);
        $this->assertSame(2, HangarBays::flightReadiness(self::DRONE, 4.0, 2)['repair_clicks']);
        $this->assertSame(['ready' => true, 'wear' => 6.0, 'sp_after' => 1.0, 'repair_clicks' => 0, 'repair_ap' => 0],
            HangarBays::flightReadiness(self::DRONE, 7.0, 2));
    }

    public function test_dispatch_refuses_a_ship_that_would_not_come_back_without_cost(): void
    {
        $this->drone(3.0); // courier run: 1 Sol out and back = 3 wear
        [$ap, $or] = [$this->ap(), (int) DB::table('colony_resources')->where('colony_id', self::COLONY_ID)->where('resource_id', 5)->value('amount')];

        try {
            $this->app->make(HangarService::class)->dispatchShip(self::COLONY_ID, 0, 'mission_courier_run', null, 'normal');
            $this->fail('expected ship_too_worn');
        } catch (GameRuleException $e) {
            $this->assertSame('ship_too_worn', $e->errorCode);
            $this->assertStringContainsString('1', $e->getMessage(), 'message names the repair AP');
        }
        $this->assertSame($ap, $this->ap());
        $this->assertSame($or, (int) DB::table('colony_resources')->where('colony_id', self::COLONY_ID)->where('resource_id', 5)->value('amount'));
    }

    public function test_short_mission_with_low_sp_is_allowed_when_the_ship_comes_back(): void
    {
        $this->drone(3.5); // below the old 25 % floor (5), but 3.5 − 3 > 0

        $this->app->make(HangarService::class)->dispatchShip(self::COLONY_ID, 0, 'mission_courier_run', null, 'normal');

        $this->assertSame('dispatched', DB::table('colony_ships')->where('colony_id', self::COLONY_ID)->where('bay', 0)->value('ship_state'));
    }

    public function test_repair_uses_config_values_and_costs_only_ap(): void
    {
        config(['game.ship_repair.sp_per_click' => 3]);
        $this->drone(10.0);
        $rg = (int) DB::table('colony_resources')->where('colony_id', self::COLONY_ID)->where('resource_id', 3)->value('amount');
        $credits = (int) DB::table('user_resources')->where('user_id', 3)->value('credits');
        $ap = $this->ap();

        $this->actingAs(User::where('user_id', 3)->firstOrFail())
            ->postJson(route('colony.hangar.repair', ['bay' => 0]))->assertOk()->assertJsonStructure(['apAvailable']);

        $this->assertSame(13.0, (float) DB::table('colony_ships')->where('colony_id', self::COLONY_ID)->where('bay', 0)->value('status_points'));
        $this->assertSame($ap - 1, $this->ap());
        $this->assertSame($rg, (int) DB::table('colony_resources')->where('colony_id', self::COLONY_ID)->where('resource_id', 3)->value('amount'));
        $this->assertSame($credits, (int) DB::table('user_resources')->where('user_id', 3)->value('credits'));
    }

    public function test_slots_carry_readiness_per_catalog_distance(): void
    {
        $this->drone(4.0);

        $ship = $this->app->make(HangarService::class)->getHangarSlots(self::COLONY_ID)[0]['ship'];

        $this->assertTrue($ship['readiness'][1]['ready']);      // wear 3
        $this->assertFalse($ship['readiness'][4]['ready']);     // wear 12
        $this->assertSame(HangarBays::flightReadiness(self::DRONE, 4.0, 4), $ship['readiness'][4]);
    }
}
