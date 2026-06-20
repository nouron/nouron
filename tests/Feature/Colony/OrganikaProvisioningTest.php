<?php

namespace Tests\Feature\Colony;

use App\Services\HangarService;
use App\Services\TrustService;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\TestCase;

/**
 * Organika provisioning sink (PR 2).
 *
 * Verpflegung (GameTick step 8a): each colony eats floor(used_supply / 4) Organika per
 * Sol. Fed → well_fed event + hunger_streak reset. Short → streak grows, driving an
 * escalating trust penalty (TrustService::hungerPenalty). Mission dispatch burns
 * Organika (sol_distance × 3) + Navigation-AP (sol_distance × 1) and gates on both.
 *
 * Fixture: Colony 1 (Springfield), user_id=3. Organika = resource 5.
 * Uses tick numbers 11400+ (no fleet orders).
 */
class OrganikaProvisioningTest extends TestCase
{
    use RefreshDatabase;

    private const COLONY_ID = 1;

    private const RES_ORGANIKA = 5;

    private const SCIENCELAB = 31;   // supply_cost 8 → used_supply 8 → food_need 2

    private const SHIP_DRONE = 85;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(TestSeeder::class)->run();
    }

    private function organika(): int
    {
        return (int) DB::table('colony_resources')
            ->where('colony_id', self::COLONY_ID)->where('resource_id', self::RES_ORGANIKA)->value('amount');
    }

    private function setOrganika(int $n): void
    {
        DB::table('colony_resources')->updateOrInsert(
            ['colony_id' => self::COLONY_ID, 'resource_id' => self::RES_ORGANIKA],
            ['amount' => $n]
        );
    }

    private function hungerStreak(): int
    {
        return (int) DB::table('glx_colonies')->where('id', self::COLONY_ID)->value('hunger_streak');
    }

    private function setHungerStreak(int $n): void
    {
        DB::table('glx_colonies')->where('id', self::COLONY_ID)->update(['hunger_streak' => $n]);
    }

    /** Replace colony 1's buildings with a controlled set so used_supply is deterministic. */
    private function onlyBuildings(array $rows): void
    {
        DB::table('colony_buildings')->where('colony_id', self::COLONY_ID)->delete();
        foreach ($rows as $r) {
            DB::table('colony_buildings')->insert(array_merge(
                ['colony_id' => self::COLONY_ID, 'instance_id' => 1, 'level' => 1, 'status_points' => 20, 'ap_spend' => 0],
                $r
            ));
        }
    }

    // ── Verpflegung (GameTick 8a) ──────────────────────────────────────────────

    public function test_well_fed_consumes_organika_and_resets_streak(): void
    {
        $this->onlyBuildings([['building_id' => self::SCIENCELAB]]);   // used 8 → need 2
        $this->setOrganika(10);
        $this->setHungerStreak(3);

        Artisan::call('game:tick', ['--tick' => 11401]);

        $this->assertSame(8, $this->organika(), 'fed colony consumes food_need (2)');
        $this->assertSame(0, $this->hungerStreak(), 'streak resets when fed');
        $this->assertDatabaseHas('trust_events', [
            'colony_id' => self::COLONY_ID, 'event_type' => 'well_fed', 'tick' => 11401,
        ]);
    }

    public function test_hunger_increments_streak_and_drains_stock(): void
    {
        $this->onlyBuildings([['building_id' => self::SCIENCELAB]]);   // need 2
        $this->setOrganika(1);                                        // short
        $this->setHungerStreak(0);

        Artisan::call('game:tick', ['--tick' => 11402]);

        $this->assertSame(0, $this->organika(), 'available stock is consumed down to 0');
        $this->assertSame(1, $this->hungerStreak(), 'first hungry Sol → streak 1');
    }

    public function test_hunger_streak_escalates(): void
    {
        $this->onlyBuildings([['building_id' => self::SCIENCELAB]]);
        $this->setOrganika(0);
        $this->setHungerStreak(3);

        Artisan::call('game:tick', ['--tick' => 11403]);

        $this->assertSame(4, $this->hungerStreak());
    }

    public function test_no_eaters_no_consumption(): void
    {
        $this->onlyBuildings([]);   // used_supply 0 → food_need 0
        $this->setOrganika(5);
        $this->setHungerStreak(2);

        Artisan::call('game:tick', ['--tick' => 11404]);

        $this->assertSame(5, $this->organika(), 'nothing eaten when there are no eaters');
        $this->assertSame(0, $this->hungerStreak(), 'streak cleared when food_need is 0');
    }

    // ── Trust penalty (escalating) ─────────────────────────────────────────────

    public function test_hunger_penalty_scales_with_streak(): void
    {
        $trust = $this->app->make(TrustService::class);

        $this->setHungerStreak(0);
        $base = $trust->calculateTrust(self::COLONY_ID, 11410);

        foreach ([1 => 2, 2 => 3, 5 => 6, 20 => 8] as $streak => $expectedPenalty) {
            $this->setHungerStreak($streak);
            $withHunger = $trust->calculateTrust(self::COLONY_ID, 11410);
            $this->assertSame(
                $base - $expectedPenalty,
                $withHunger,
                "streak {$streak} must subtract {$expectedPenalty} trust (cap 8)"
            );
        }
    }

    // ── Mission dispatch provisions ────────────────────────────────────────────

    private function setupDockedDrone(): HangarService
    {
        DB::table('colony_buildings')->updateOrInsert(
            ['colony_id' => self::COLONY_ID, 'building_id' => 44, 'instance_id' => 1],
            ['level' => 1, 'status_points' => 20, 'ap_spend' => 0]
        );
        DB::table('colony_ships')->updateOrInsert(
            ['colony_id' => self::COLONY_ID, 'ship_id' => self::SHIP_DRONE],
            ['hangar_instance_id' => 1, 'ship_state' => 'docked', 'level' => 1, 'status_points' => 20, 'ap_spend' => 0]
        );

        return $this->app->make(HangarService::class);
    }

    public function test_dispatch_consumes_organika_provisions(): void
    {
        config(['game.bypass.resource_costs' => false, 'game.bypass.ap_checks' => false]);
        $svc = $this->setupDockedDrone();
        $this->setOrganika(20);

        $svc->dispatchShip(self::COLONY_ID, 1, 'Outer Belt', 3);   // 3 × 3 = 9 Organika

        $this->assertSame(11, $this->organika());
        $this->assertSame('dispatched', DB::table('colony_ships')
            ->where('colony_id', self::COLONY_ID)->where('ship_id', self::SHIP_DRONE)->value('ship_state'));
    }

    public function test_dispatch_blocked_without_organika(): void
    {
        config(['game.bypass.resource_costs' => false, 'game.bypass.ap_checks' => false]);
        $svc = $this->setupDockedDrone();
        $this->setOrganika(10);   // need 5 × 3 = 15

        try {
            $svc->dispatchShip(self::COLONY_ID, 1, 'Far Rim', 5);
            $this->fail('dispatch must throw when Organika is insufficient');
        } catch (RuntimeException $e) {
            // expected
        }

        $this->assertSame(10, $this->organika(), 'no Organika spent on a blocked dispatch');
        $this->assertSame('docked', DB::table('colony_ships')
            ->where('colony_id', self::COLONY_ID)->where('ship_id', self::SHIP_DRONE)->value('ship_state'));
    }

    public function test_dispatch_blocked_without_nav_ap(): void
    {
        config(['game.bypass.resource_costs' => false, 'game.bypass.ap_checks' => false]);
        $svc = $this->setupDockedDrone();
        $this->setOrganika(999);   // plenty of provisions

        // Base Nav-AP is 6/Sol; sol_distance 7 → nav cost 7 > 6 → blocked.
        $this->expectException(RuntimeException::class);
        $svc->dispatchShip(self::COLONY_ID, 1, 'Deep Black', 7);
    }
}
