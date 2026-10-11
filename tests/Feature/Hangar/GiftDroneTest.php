<?php

namespace Tests\Feature\Hangar;

use App\Models\User;
use App\Services\HangarService;
use App\Services\ProjectBonusService;
use App\Services\TickService;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/** T30 §13.2: hangar level 0 → 1 lands exactly one free gift drone on the apron (bay 0). */
class GiftDroneTest extends TestCase
{
    use RefreshDatabase;

    private const COLONY_ID = 1;

    private const USER_ID = 3;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(TestSeeder::class)->run();
        $this->app->instance(TickService::class, new TickService(100));
        config(['game.bypass.ap_checks' => true]);
        DB::table('colony_hangar_missions')->where('colony_id', self::COLONY_ID)->delete();
        DB::table('colony_ships')->where('colony_id', self::COLONY_ID)->delete();
        DB::table('colony_buildings')->where('colony_id', self::COLONY_ID)->where('building_id', 44)->delete();
    }

    /** A placed hall one AP short of its next level. */
    private function hallReadyToLevel(int $level): void
    {
        $threshold = $this->app->make(ProjectBonusService::class)->effectiveApForLevelup(self::COLONY_ID, 10);
        DB::table('colony_buildings')->updateOrInsert(
            ['colony_id' => self::COLONY_ID, 'building_id' => 44, 'instance_id' => 1],
            ['level' => $level, 'status_points' => 20, 'ap_spend' => $threshold - 1, 'tile_x' => 5, 'tile_y' => 0]
        );
    }

    private function invest(): array
    {
        return $this->actingAs(User::where('user_id', self::USER_ID)->firstOrFail())
            ->postJson(route('colony.building.invest'), ['building_id' => 44, 'instance_id' => 1])
            ->assertOk()->json();
    }

    private function ships(): Collection
    {
        return DB::table('colony_ships')->where('colony_id', self::COLONY_ID)->get();
    }

    public function test_level_one_lands_one_docked_drone_on_the_apron_for_free(): void
    {
        $credits = (int) DB::table('user_resources')->where('user_id', self::USER_ID)->value('credits');
        $debt = (int) DB::table('runs')->where('id', 1)->value('nexus_debt');
        $this->hallReadyToLevel(0);

        $this->assertTrue($this->invest()['leveled_up']);

        $ships = $this->ships();
        $this->assertCount(1, $ships);
        $this->assertSame([85, 0, 'docked'], [(int) $ships[0]->ship_id, (int) $ships[0]->bay, $ships[0]->ship_state]);
        $this->assertSame($credits, (int) DB::table('user_resources')->where('user_id', self::USER_ID)->value('credits'));
        $this->assertSame($debt, (int) DB::table('runs')->where('id', 1)->value('nexus_debt'));
        $this->assertSame(1, $this->app->make(HangarService::class)->freeBay(self::COLONY_ID), 'bay 1 stays free');
        $this->assertTrue(DB::table('colony_log')->where('user', self::USER_ID)->where('event', 'hangar.gift_drone')->exists());
    }

    public function test_level_two_lands_no_second_drone(): void
    {
        $this->hallReadyToLevel(0);
        $this->invest();
        $this->hallReadyToLevel(1);
        $this->invest();

        $this->assertCount(1, $this->ships());
    }

    public function test_demolish_to_zero_and_rebuild_lands_no_second_drone(): void
    {
        $svc = $this->app->make(HangarService::class);
        $this->hallReadyToLevel(1);
        $this->assertTrue($svc->ensureGiftDrone(self::COLONY_ID));
        DB::table('colony_buildings')->where('colony_id', self::COLONY_ID)->where('building_id', 44)
            ->update(['level' => 0, 'tile_x' => null, 'tile_y' => null]);
        $this->assertFalse($svc->ensureGiftDrone(self::COLONY_ID), 'level 0: nothing');

        $this->hallReadyToLevel(0);
        $this->invest();

        $this->assertCount(1, $this->ships());
    }

    public function test_ensure_is_idempotent(): void
    {
        $svc = $this->app->make(HangarService::class);
        $this->hallReadyToLevel(1);

        $this->assertTrue($svc->ensureGiftDrone(self::COLONY_ID));
        $this->assertFalse($svc->ensureGiftDrone(self::COLONY_ID));
        $this->assertCount(1, $this->ships());
    }
}
