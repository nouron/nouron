<?php

namespace Tests\Feature\Hangar;

use App\Models\User;
use App\Services\HangarService;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * T14 — "Hangar unter Schiffsstufe" (GDD §7): the hangar level is the ship class
 * (Drohne Lv1, Frachter Lv2, Korvette Lv3). A ship whose hangar sits below its
 * class is inactive: it cannot start a mission, stays assigned, and is active
 * again as soon as the hangar is back on the ship's level. Derived from the
 * levels, never stored. A mission already under way runs on (Bestandsschutz).
 */
class HangarShipInactiveTest extends TestCase
{
    use RefreshDatabase;

    private const COLONY_ID = 1;

    private const USER_ID = 3;

    private const HANGAR_BUILDING = 44;

    private const INSTANCE = 1;

    private const SHIP_FREIGHTER = 47;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(TestSeeder::class)->run();

        DB::table('colony_hangar_missions')->where('colony_id', self::COLONY_ID)->delete();
        DB::table('colony_ships')->where('colony_id', self::COLONY_ID)->update(['hangar_instance_id' => null]);
        DB::table('colony_ships')->where('colony_id', self::COLONY_ID)->where('ship_id', self::SHIP_FREIGHTER)->update([
            'hangar_instance_id' => self::INSTANCE,
            'ship_state' => 'docked',
            'status_points' => 20,
        ]);
        $this->setHangarLevel(2);
    }

    public function test_freighter_is_active_in_a_level_2_hangar(): void
    {
        $ship = $this->slotShip();

        $this->assertFalse($ship['inactive']);
        $this->assertSame(2, $ship['required_hangar_level']);
    }

    public function test_freighter_turns_inactive_when_decay_drops_its_hangar_below_the_ship_class(): void
    {
        DB::table('colony_buildings')->where($this->hangarKey())->update(['status_points' => 0.1]);

        Artisan::call('game:tick', ['--tick' => 11004]);

        $this->assertSame(1, (int) DB::table('colony_buildings')->where($this->hangarKey())->value('level'));
        $ship = $this->slotShip();
        $this->assertTrue($ship['inactive']);
        $this->assertNotEmpty($ship['inactive_reason'], 'the UI needs the reason, not just the flag');
        $this->assertSame(self::INSTANCE, (int) DB::table('colony_ships')
            ->where('colony_id', self::COLONY_ID)->where('ship_id', self::SHIP_FREIGHTER)->value('hangar_instance_id'),
            'an inactive ship stays assigned to its hangar');
    }

    public function test_dispatch_of_an_inactive_ship_is_rejected_with_a_machine_code(): void
    {
        $this->setHangarLevel(1);

        $response = $this->actingAs(User::find(self::USER_ID))
            ->postJson(route('colony.hangar.dispatch', ['instanceId' => self::INSTANCE]), [
                'mission_key' => 'mission_supply_run',
                'difficulty' => 'easy',
            ]);

        $response->assertStatus(422)
            ->assertJsonPath('ok', false)
            ->assertJsonPath('error', 'ship_inactive')
            ->assertJsonPath('message', __('colony.hangar_ship_inactive', ['level' => 2]));
        $this->assertSame('docked', $this->shipState());
        $this->assertSame(0, DB::table('colony_hangar_missions')->where('colony_id', self::COLONY_ID)->count());
    }

    public function test_ship_is_active_again_once_the_hangar_is_back_on_its_level(): void
    {
        $this->setHangarLevel(1);
        $this->assertTrue($this->slotShip()['inactive']);

        $this->setHangarLevel(2);

        $this->assertFalse($this->slotShip()['inactive']);
        $this->app->make(HangarService::class)
            ->dispatchShip(self::COLONY_ID, self::INSTANCE, 'mission_supply_run', null, 'easy');
        $this->assertSame('dispatched', $this->shipState());
    }

    public function test_a_running_mission_is_not_aborted_when_the_hangar_drops_below_the_ship_class(): void
    {
        Config::set('game.missions.difficulty.base_chance', ['easy' => 1.0, 'normal' => 1.0, 'hard' => 1.0]);
        DB::table('colony_ships')->where('colony_id', self::COLONY_ID)->where('ship_id', self::SHIP_FREIGHTER)
            ->update(['ship_state' => 'dispatched']);
        $missionId = DB::table('colony_hangar_missions')->insertGetId([
            'colony_id' => self::COLONY_ID,
            'instance_id' => self::INSTANCE,
            'ship_id' => self::SHIP_FREIGHTER,
            'destination' => 'mission_supply_run',
            'sol_distance' => 1,
            'target' => null,
            'difficulty' => 'easy',
            'dispatch_tick' => 20100,
            'recall_tick' => null,
            'state' => 'active',
            'created_at' => now(),
        ]);
        $this->setHangarLevel(1);

        Artisan::call('game:tick', ['--run' => 1, '--tick' => 20102]);

        $this->assertSame('completed', DB::table('colony_hangar_missions')->where('id', $missionId)->value('state'));
        $this->assertSame('docked', $this->shipState());
        $this->assertTrue($this->slotShip()['inactive'], 'back home, the ship is inactive while the hangar stays too low');
    }

    public function test_hangar_screen_data_carries_the_inactive_state(): void
    {
        $this->setHangarLevel(1);

        $response = $this->actingAs(User::find(self::USER_ID))->get(route('colony.hangar'));

        $response->assertOk();
        $slots = $response->viewData('slots');
        $slot = collect($slots)->firstWhere('instance_id', self::INSTANCE);
        $this->assertTrue($slot['ship']['inactive']);
        $this->assertSame(__('colony.hangar_ship_inactive', ['level' => 2]), $slot['ship']['inactive_reason']);
    }

    // ── helpers ───────────────────────────────────────────────────────────────

    /** @return array<string, int> */
    private function hangarKey(): array
    {
        return ['colony_id' => self::COLONY_ID, 'building_id' => self::HANGAR_BUILDING, 'instance_id' => self::INSTANCE];
    }

    private function setHangarLevel(int $level): void
    {
        DB::table('colony_buildings')->where($this->hangarKey())->update(['level' => $level, 'status_points' => 20]);
    }

    /** @return array<string, mixed> */
    private function slotShip(): array
    {
        $slots = $this->app->make(HangarService::class)->getHangarSlots(self::COLONY_ID);
        $slot = collect($slots)->firstWhere('instance_id', self::INSTANCE);
        $this->assertNotNull($slot['ship']);

        return $slot['ship'];
    }

    private function shipState(): string
    {
        return (string) DB::table('colony_ships')
            ->where('colony_id', self::COLONY_ID)->where('ship_id', self::SHIP_FREIGHTER)->value('ship_state');
    }
}
