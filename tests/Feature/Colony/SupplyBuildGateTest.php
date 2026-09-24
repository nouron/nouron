<?php

namespace Tests\Feature\Colony;

use App\Models\User;
use App\Services\AdvisorService;
use App\Services\HarvesterEntitlementService;
use App\Services\ResourcesService;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Supply as build gate (GDD §6 "Supply als Bau-Gate", A14 Owner decision
 * 2026-09-24): a building level-up needs free supply (cap − workplaces) for the
 * supply_cost of the next level, otherwise 422 supply_limit before any AP or
 * Regolith is spent. Command Center and housing are exempt — they create the
 * housing that leads out of over-capacity. On placement the first Harvester
 * stays unchecked (bootstrap), the second one is checked.
 *
 * Fixture colony 1 (user 3): CC 25 level 3, housing 28 instance 1 level 2,
 * sciencelab 31 level 1, harvester 27 instance 1 level 1 (unplaced). Supply costs
 * pinned in setUp: sciencelab 6, harvester 2. The cap is set directly: user_resources.supply = workplaces + free.
 */
class SupplyBuildGateTest extends TestCase
{
    use RefreshDatabase;

    private const COLONY_ID = 1;

    private const USER_ID = 3;

    private const CC = 25;

    private const HARVESTER = 27;

    private const HOUSING = 28;

    private const SCIENCELAB = 31;

    private const AGRARDOM = 41;

    private const BAR = 52;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(TestSeeder::class)->run();

        config([
            'game.ap.base' => 30,
            'game.bypass.ap_checks' => false,
            'game.bypass.resource_costs' => false,
            'game.bypass.supply_checks' => false,
        ]);
        DB::table('colony_resources')->updateOrInsert(['colony_id' => self::COLONY_ID, 'resource_id' => 3], ['amount' => 5000]);
        DB::table('colony_buildings')->where('colony_id', self::COLONY_ID)->update(['ap_spend' => 0]);
        DB::table('buildings')->where('id', self::SCIENCELAB)->update(['supply_cost' => 6]);
        DB::table('buildings')->where('id', self::HARVESTER)->update(['supply_cost' => 2]);
    }

    private function user(): User
    {
        return User::where('user_id', self::USER_ID)->firstOrFail();
    }

    private function setFree(int $free, int $departed = 0): void
    {
        $breakdown = $this->app->make(ResourcesService::class)->getSupplyBreakdown(self::COLONY_ID);
        $workplaces = $breakdown['cap'] - $breakdown['free'];
        DB::table('user_resources')->where('user_id', self::USER_ID)->update(['supply' => $workplaces + $free]);
        DB::table('glx_colonies')->where('id', self::COLONY_ID)->update(['overcap_departed' => $departed]);
        $this->assertSame($free, $this->app->make(ResourcesService::class)->getFreeSupply(self::COLONY_ID));
    }

    private function invest(int $buildingId, int $instanceId = 1)
    {
        return $this->actingAs($this->user())
            ->postJson(route('colony.building.invest'), ['building_id' => $buildingId, 'instance_id' => $instanceId]);
    }

    private function apSpend(int $buildingId, int $instanceId = 1): int
    {
        return (int) DB::table('colony_buildings')
            ->where('colony_id', self::COLONY_ID)->where('building_id', $buildingId)->where('instance_id', $instanceId)
            ->value('ap_spend');
    }

    private function ap(): int
    {
        return $this->app->make(AdvisorService::class)->getAvailableActionPoints(self::COLONY_ID);
    }

    public function test_level_up_beyond_the_cap_is_rejected_before_spending_anything(): void
    {
        $this->setFree(5); // sciencelab needs 6
        $apBefore = $this->ap();

        $response = $this->invest(self::SCIENCELAB);

        $response->assertStatus(422)->assertJsonPath('ok', false)->assertJsonPath('error', 'supply_limit');
        $this->assertSame(0, $this->apSpend(self::SCIENCELAB), 'no AP progress on a rejected invest');
        $this->assertSame($apBefore, $this->ap(), 'no AP locked on a rejected invest');
    }

    public function test_level_up_within_the_cap_is_allowed(): void
    {
        $this->setFree(6);

        $this->invest(self::SCIENCELAB)->assertOk()->assertJsonPath('ok', true);
        $this->assertSame(1, $this->apSpend(self::SCIENCELAB));
    }

    public function test_unfilled_workplaces_keep_the_gate_closed_after_a_departure(): void
    {
        $this->setFree(-8, 8); // understaffed: nobody homeless, but 8 workplaces unfilled

        $this->invest(self::SCIENCELAB)->assertStatus(422)->assertJsonPath('error', 'supply_limit');
    }

    public function test_command_center_and_housing_are_exempt_even_with_negative_free_supply(): void
    {
        $this->setFree(-10);

        $this->invest(self::CC)->assertOk()->assertJsonPath('ok', true);
        $this->invest(self::HOUSING)->assertOk()->assertJsonPath('ok', true);
        $this->assertSame(1, $this->apSpend(self::CC));
        $this->assertSame(1, $this->apSpend(self::HOUSING));
    }

    public function test_first_harvester_placement_is_not_checked(): void
    {
        $this->setFree(-10);
        DB::table('colony_tiles')->where('colony_id', self::COLONY_ID)->where('q', 3)->where('r', 0)->delete();
        DB::table('colony_tiles')->insert([
            'colony_id' => self::COLONY_ID, 'q' => 3, 'r' => 0, 'ring' => 3, 'tile_type' => 'regolith_normal',
            'is_explored' => 1, 'is_colony_zone' => 0, 'is_deep_scanned' => 0, 'resource_amount' => 300, 'resource_max' => 300,
        ]);

        $this->actingAs($this->user())
            ->postJson(route('colony.building.place'), ['building_id' => self::HARVESTER, 'q' => 3, 'r' => 0])
            ->assertOk()->assertJsonPath('ok', true);
    }

    private function buildableTile(int $q, int $r): void
    {
        DB::table('colony_tiles')->updateOrInsert(
            ['colony_id' => self::COLONY_ID, 'q' => $q, 'r' => $r],
            ['ring' => 1, 'tile_type' => 'terrain_empty', 'is_explored' => 1, 'is_colony_zone' => 1, 'is_deep_scanned' => 0]
        );
    }

    private function place(int $buildingId, int $q, int $r)
    {
        $this->buildableTile($q, $r);

        return $this->actingAs($this->user())
            ->postJson(route('colony.building.place'), ['building_id' => $buildingId, 'q' => $q, 'r' => $r]);
    }

    /** Workplaces of every building on level ≥ 1 — the load without any level-0 reserve. */
    private function builtWorkplaces(): int
    {
        return (int) DB::table('colony_buildings as cb')
            ->join('buildings as b', 'b.id', '=', 'cb.building_id')
            ->where('cb.colony_id', self::COLONY_ID)
            ->where('cb.level', '>', 0)
            ->sum(DB::raw('cb.level * COALESCE(b.supply_cost, 0)'));
    }

    public function test_placement_is_blocked_when_level_zero_reserves_fill_the_cap(): void
    {
        DB::table('colony_resources')->updateOrInsert(['colony_id' => self::COLONY_ID, 'resource_id' => 4], ['amount' => 5000]);
        DB::table('buildings')->where('id', self::AGRARDOM)->update(['supply_cost' => 2]);
        DB::table('buildings')->where('id', self::BAR)->update(['supply_cost' => 5]);
        $this->setFree(6);

        $this->place(self::AGRARDOM, 1, 0)->assertOk()->assertJsonPath('freeSupply', 4);

        // The Agrardom is still on level 0, but its first level already holds 2 of the
        // 6 free workplaces — the Bar (5) no longer fits.
        $this->place(self::BAR, 0, 1)->assertStatus(422)->assertJsonPath('error', 'supply_limit');
        $this->assertNull(DB::table('colony_buildings')
            ->where('colony_id', self::COLONY_ID)->where('building_id', self::BAR)->value('tile_x'));
    }

    public function test_first_level_of_a_placed_building_never_fails_on_supply(): void
    {
        DB::table('buildings')->where('id', self::AGRARDOM)->update(['supply_cost' => 2]);
        $this->setFree(2);
        $this->place(self::AGRARDOM, 1, 0)->assertOk();

        // Housing lost after placement: the cap now sits below the reserve.
        DB::table('user_resources')->where('user_id', self::USER_ID)->update(['supply' => $this->builtWorkplaces() + 1]);

        $apSpend = $this->apSpend(self::AGRARDOM);
        $this->invest(self::AGRARDOM)->assertOk()->assertJsonPath('ok', true);
        $this->assertSame($apSpend + 1, $this->apSpend(self::AGRARDOM));
    }

    public function test_second_harvester_placement_is_checked_against_free_supply(): void
    {
        $this->app->make(HarvesterEntitlementService::class)->grantPurchase(self::USER_ID);
        DB::table('colony_buildings')
            ->where('colony_id', self::COLONY_ID)->where('building_id', self::HARVESTER)->where('instance_id', 1)
            ->update(['tile_x' => 3, 'tile_y' => 0]);
        DB::table('colony_tiles')->where('colony_id', self::COLONY_ID)->whereIn('q', [3, -3])->where('r', 0)->delete();
        DB::table('colony_tiles')->insert([
            ['colony_id' => self::COLONY_ID, 'q' => 3, 'r' => 0, 'ring' => 3, 'tile_type' => 'regolith_normal', 'is_explored' => 1, 'is_colony_zone' => 0, 'is_deep_scanned' => 0, 'resource_amount' => 300, 'resource_max' => 300],
            ['colony_id' => self::COLONY_ID, 'q' => -3, 'r' => 0, 'ring' => 3, 'tile_type' => 'regolith_poor', 'is_explored' => 1, 'is_colony_zone' => 0, 'is_deep_scanned' => 0, 'resource_amount' => 160, 'resource_max' => 160],
        ]);
        $place = fn () => $this->actingAs($this->user())->postJson(route('colony.building.place'), [
            'building_id' => self::HARVESTER, 'q' => -3, 'r' => 0, 'instance_id' => 2,
        ]);

        $this->setFree(1); // harvester supply_cost 2
        $place()->assertStatus(422)->assertJsonPath('error', 'supply_limit');
        $this->assertFalse(DB::table('colony_buildings')->where('colony_id', self::COLONY_ID)
            ->where('building_id', self::HARVESTER)->where('instance_id', 2)->exists());

        $this->setFree(2);
        $place()->assertOk()->assertJsonPath('ok', true);
    }
}
