<?php

namespace Tests\Feature\Colony;

use App\Models\User;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Second Harvester instance gate (GDD §4c "Die zweite Instanz braucht ein Gate",
 * freigegeben 2026-08-03).
 *
 * Instance 1 keeps the Regolith-free bootstrap exemption (§3). Instance 2 is a
 * paid expansion: requires CommandCenter Lv3 AND costs a flat 100 Regolith —
 * not the generic buildCostFor() path (Harvester has no build_cost entry).
 *
 * Fixture: Colony 1 (Springfield), user_id=3 (Bart), harvester building_id=27,
 * CC building_id=25.
 */
class HarvesterSecondInstanceTest extends TestCase
{
    use RefreshDatabase;

    private const COLONY_ID = 1;

    private const BART_USER_ID = 3;

    private const HARVESTER_ID = 27;

    private const CC_ID = 25;

    private const RES_REGOLITH = 3;

    private const TRUST_RES_ID = 12;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(TestSeeder::class)->run();

        DB::table('colony_resources')->updateOrInsert(
            ['colony_id' => self::COLONY_ID, 'resource_id' => self::TRUST_RES_ID],
            ['amount' => 0]
        );
        DB::table('trust_events')->where('colony_id', self::COLONY_ID)->delete();

        // Instance 1 already placed and running.
        DB::table('colony_buildings')->updateOrInsert(
            ['colony_id' => self::COLONY_ID, 'building_id' => self::HARVESTER_ID, 'instance_id' => 1],
            ['level' => 1, 'status_points' => 16, 'ap_spend' => 0, 'tile_x' => 3, 'tile_y' => 0, 'pending_until_tick' => null]
        );

        // No instance 2 yet.
        DB::table('colony_buildings')
            ->where('colony_id', self::COLONY_ID)->where('building_id', self::HARVESTER_ID)->where('instance_id', 2)
            ->delete();

        DB::table('colony_tiles')->insertOrIgnore([
            ['colony_id' => self::COLONY_ID, 'q' => 3, 'r' => 0, 'ring' => 3, 'tile_type' => 'regolith_normal', 'is_explored' => 1, 'is_colony_zone' => 0, 'is_deep_scanned' => 0, 'resource_amount' => 300, 'resource_max' => 300],
            ['colony_id' => self::COLONY_ID, 'q' => -3, 'r' => 0, 'ring' => 3, 'tile_type' => 'regolith_poor', 'is_explored' => 1, 'is_colony_zone' => 0, 'is_deep_scanned' => 0, 'resource_amount' => 160, 'resource_max' => 160],
        ]);

        // Ample Construction-AP so the AP gate never blocks these assertions.
        config(['game.ap.base' => 20]);
        config(['game.bypass.ap_checks' => false]);
        config(['game.bypass.resource_costs' => false]);
        config(['game.bypass.supply_checks' => true]);
    }

    private function makeUser(): User
    {
        return User::where('user_id', self::BART_USER_ID)->firstOrFail();
    }

    private function setCcLevel(int $level): void
    {
        DB::table('colony_buildings')->updateOrInsert(
            ['colony_id' => self::COLONY_ID, 'building_id' => self::CC_ID, 'instance_id' => 1],
            ['level' => $level, 'status_points' => 20, 'ap_spend' => 0]
        );
    }

    private function setRegolith(int $amount): void
    {
        DB::table('colony_resources')->updateOrInsert(
            ['colony_id' => self::COLONY_ID, 'resource_id' => self::RES_REGOLITH],
            ['amount' => $amount]
        );
    }

    private function regolithAmount(): int
    {
        return (int) DB::table('colony_resources')
            ->where('colony_id', self::COLONY_ID)->where('resource_id', self::RES_REGOLITH)
            ->value('amount');
    }

    private function placeSecondInstance()
    {
        return $this->actingAs($this->makeUser())
            ->postJson(route('colony.building.place'), [
                'building_id' => self::HARVESTER_ID,
                'q' => -3,
                'r' => 0,
                'instance_id' => 2,
            ]);
    }

    private function instance2Row(): ?object
    {
        return DB::table('colony_buildings')
            ->where('colony_id', self::COLONY_ID)->where('building_id', self::HARVESTER_ID)->where('instance_id', 2)
            ->first();
    }

    public function test_second_instance_rejected_before_cc_level_3(): void
    {
        $this->setCcLevel(2);
        $this->setRegolith(500);

        $response = $this->placeSecondInstance();

        $response->assertStatus(422)->assertJsonPath('ok', false);
        $this->assertSame('harvester_second_instance_cc_gate', $response->json('error'));
        $this->assertNull($this->instance2Row(), 'No instance 2 row must be created when the CC gate fails');
        $this->assertSame(500, $this->regolithAmount(), 'Regolith must not be deducted when the gate fails');
    }

    public function test_second_instance_accepted_after_cc_level_3_with_enough_regolith(): void
    {
        $this->setCcLevel(3);
        $this->setRegolith(150);

        $response = $this->placeSecondInstance();

        $response->assertOk()->assertJsonPath('ok', true);
        $row = $this->instance2Row();
        $this->assertNotNull($row, 'Instance 2 must be created once the CC gate is met');
        $this->assertSame(-3, (int) $row->tile_x);
        $this->assertSame(150 - 100, $this->regolithAmount(), 'Second instance must cost exactly 100 Regolith');
    }

    /**
     * Instance 2 is placed at level=0 (the same convention as every other
     * instanced building, e.g. Housing) — it does not produce until levelled
     * up via the generic investBuilding() endpoint. This is intentional per
     * GDD §4c ("Sie ist eine Expansion und wird bezahlt wie jede andere") and
     * NOT a dead end: harvester.ap_for_levelup=10 (testdata), so 10
     * Construction-AP clicks bring it to level 1, after which it produces
     * exactly like instance 1.
     */
    public function test_second_instance_produces_only_after_being_leveled_up_via_invest(): void
    {
        $this->setCcLevel(3);
        $this->setRegolith(150);
        $this->placeSecondInstance()->assertOk()->assertJsonPath('ok', true);

        $this->assertSame(0, $this->instance2Row()->level, 'Freshly placed instance 2 starts at level 0, same as any other instanced building');

        // Disable instance 1 for this test so only instance 2's production is measured.
        DB::table('colony_buildings')
            ->where('colony_id', self::COLONY_ID)->where('building_id', self::HARVESTER_ID)->where('instance_id', 1)
            ->update(['level' => 0]);

        DB::table('colony_resources')->updateOrInsert(
            ['colony_id' => self::COLONY_ID, 'resource_id' => self::RES_REGOLITH],
            ['amount' => 1000]
        );
        $before = $this->regolithAmount();

        Artisan::call('game:tick', ['--tick' => 25000]);
        $this->assertSame($before, $this->regolithAmount(), 'Level-0 instance 2 must not produce before being invested');

        // ap_for_levelup for building 27 is 10 (testdata); placement already spent
        // 1 (ap_spend=1) — 9 more Construction-AP clicks reach the threshold.
        for ($i = 0; $i < 9; $i++) {
            $this->actingAs($this->makeUser())
                ->postJson(route('colony.building.invest'), ['building_id' => self::HARVESTER_ID, 'instance_id' => 2])
                ->assertOk();
        }
        $this->assertSame(1, $this->instance2Row()->level, 'Instance 2 must reach level 1 after ap_for_levelup Construction-AP investments');

        $before = $this->regolithAmount();
        Artisan::call('game:tick', ['--tick' => 25001]);
        $this->assertGreaterThan($before, $this->regolithAmount(), 'Instance 2 must produce once levelled to 1, exactly like instance 1');
    }

    public function test_second_instance_rejected_without_enough_regolith(): void
    {
        $this->setCcLevel(3);
        $this->setRegolith(50);

        $response = $this->placeSecondInstance();

        $response->assertStatus(422)->assertJsonPath('ok', false);
        $this->assertSame('resource_limit', $response->json('error'));
        $this->assertNull($this->instance2Row(), 'No instance 2 row must be created when Regolith is insufficient');
        $this->assertSame(50, $this->regolithAmount(), 'Regolith must not be deducted when the check fails');
    }

    public function test_first_instance_stays_regolith_free_regardless_of_cc_level(): void
    {
        // Regression guard: the gate/cost must apply ONLY to instance 2 — moving
        // instance 1 stays free at any CC level (bootstrap exemption, GDD §3).
        $this->setCcLevel(1);
        $this->setRegolith(0);

        $response = $this->actingAs($this->makeUser())
            ->postJson(route('colony.building.place'), [
                'building_id' => self::HARVESTER_ID,
                'q' => -3,
                'r' => 0,
            ]);

        $response->assertOk()->assertJsonPath('ok', true);
        $this->assertSame(0, $this->regolithAmount(), 'Instance 1 relocation must stay free');
    }
}
