<?php

namespace Tests\Feature\Techtree;

use App\Services\Techtree\BuildingService;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\CreatesForeignColony;
use Tests\TestCase;

class BuildingServiceTest extends TestCase
{
    use CreatesForeignColony;
    use RefreshDatabase;

    protected BuildingService $service;

    protected int $entityId = 46; // infirmary (no max_level, upgradable)

    protected int $colonyId = 1;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(TestSeeder::class)->run();
        $this->service = $this->app->make(BuildingService::class);

        // Provide a construction advisor for colony 1 so invest() has AP available.
        DB::table('advisors')->where('colony_id', $this->colonyId)->delete();
        DB::table('advisors')->insert([
            'user_id' => 3,
            'personell_id' => 35, // engineer (construction AP)
            'colony_id' => $this->colonyId,
            'rank' => 3,  // 12 AP — enough for any invest test
            'active_ticks' => 0,
            'unavailable_until_tick' => null,
        ]);

        // A building needs a tile before it can gain a level (BuildingService::isPlaced()).
        // The fixture carries no tile coordinates, so place the rows these tests level up.
        DB::table('colony_buildings')
            ->where(['colony_id' => $this->colonyId, 'building_id' => $this->entityId])
            ->update(['tile_x' => 2, 'tile_y' => -1]);
        DB::table('colony_buildings')
            ->where(['colony_id' => $this->colonyId, 'building_id' => 28, 'instance_id' => 1])
            ->update(['tile_x' => 0, 'tile_y' => 1]);
    }

    public function test_get_entities(): void
    {
        $result = $this->service->getEntities();
        $this->assertTrue($result->isNotEmpty());
    }

    public function test_get_entity(): void
    {
        $result = $this->service->getEntity($this->entityId);
        $this->assertNotNull($result);
        $this->assertEquals(46, $result->id);
        $this->assertFalse($this->service->getEntity(99999));

        $this->expectException(\InvalidArgumentException::class);
        $this->service->getEntity(-1);
    }

    public function test_get_entity_costs(): void
    {
        $result = $this->service->getEntityCosts($this->entityId);
        $this->assertTrue($result->isNotEmpty());
    }

    public function test_get_colony_entity(): void
    {
        $result = $this->service->getColonyEntity($this->colonyId, $this->entityId);
        $this->assertNotNull($result);
        $this->assertEquals(3, $result->level);  // infirmary level=3 on colony 1 per test data
    }

    /**
     * Second colony (CC 5) whose infirmary has only 1 of 10 AP invested — the
     * "partially funded" case (was fixture colony 2 until its removal 2026-09-23).
     */
    private function colonyWithPartialInfirmary(): int
    {
        $colonyId = $this->createForeignColony([25 => 5, $this->entityId => 1])['colony_id'];
        DB::table('colony_buildings')
            ->where(['colony_id' => $colonyId, 'building_id' => $this->entityId])
            ->update(['ap_spend' => 1, 'tile_x' => 2, 'tile_y' => -1]);

        return $colonyId;
    }

    public function test_get_colony_entities(): void
    {
        $result = $this->service->getColonyEntities($this->colonyId);
        $this->assertTrue($result->isNotEmpty());
    }

    public function test_check_required_action_points(): void
    {
        // infirmary (46): ap_spend=10, ap_for_levelup=10 -> passes
        $this->assertTrue($this->service->checkRequiredActionPoints($this->colonyId, 46));
        // housingComplex (28): ap_spend=0, ap_for_levelup=10 -> fails
        $this->assertFalse($this->service->checkRequiredActionPoints($this->colonyId, 28));
        // second colony, infirmary: ap_spend=1, ap_for_levelup=10 -> fails
        $this->assertFalse($this->service->checkRequiredActionPoints($this->colonyWithPartialInfirmary(), 46));
    }

    public function test_levelup(): void
    {
        // infirmary.max_level is now capped at 3 (2026-08-25, Ausbaustufen-Umstellung)
        // and colony 1's infirmary fixture is already at level 3 — this test is about
        // the generic level/AP mechanics, not the tier cap, so lift the cap locally.
        DB::table('buildings')->where('id', $this->entityId)->update(['max_level' => null]);

        $before = $this->service->getColonyEntity($this->colonyId, $this->entityId);
        $result = $this->service->levelup($this->colonyId, $this->entityId);
        $this->assertTrue($result);
        $after = $this->service->getColonyEntity($this->colonyId, $this->entityId);
        $this->assertEquals($before->level + 1, $after->level);

        // housingComplex (28): ap_spend=0 fails AP check
        $result = $this->service->levelup($this->colonyId, 28);
        $this->assertFalse($result);

        // second colony, infirmary: ap_spend=1 fails AP check
        $result = $this->service->levelup($this->colonyWithPartialInfirmary(), $this->entityId);
        $this->assertFalse($result);
    }

    /**
     * The techtree honours game.bypass.ap_checks like every other AP gate.
     *
     * It used to ignore the flag entirely, so with dev mode on the whole game was
     * free-clickable except the techtree — invest() still refused without AP.
     */
    public function test_invest_is_rejected_without_ap_when_the_gate_is_live(): void
    {
        // Empty the pool for real: advisors gone AND game.ap.base zeroed — the colony
        // gets a base AP allowance regardless of staffing.
        config(['game.bypass.ap_checks' => false, 'game.ap.base' => 0]);
        DB::table('advisors')->where('colony_id', $this->colonyId)->delete();

        $this->assertFalse(
            $this->service->invest($this->colonyId, $this->entityId, 'add', 1),
            'Without AP and with the gate live, invest() must refuse.'
        );
    }

    public function test_invest_succeeds_without_ap_when_ap_checks_are_bypassed(): void
    {
        config(['game.bypass.ap_checks' => true, 'game.ap.base' => 0]);
        DB::table('advisors')->where('colony_id', $this->colonyId)->delete();

        DB::table('colony_buildings')
            ->where(['colony_id' => $this->colonyId, 'building_id' => $this->entityId])
            ->update(['ap_spend' => 0]);

        $this->assertTrue(
            $this->service->invest($this->colonyId, $this->entityId, 'add', 1),
            'With ap_checks bypassed, invest() must not be blocked by an empty AP pool.'
        );

        $this->assertSame(1, (int) DB::table('colony_buildings')
            ->where(['colony_id' => $this->colonyId, 'building_id' => $this->entityId])
            ->value('ap_spend'));

        $this->assertSame(
            0,
            DB::table('locked_actionpoints')->where('colony_id', $this->colonyId)->count(),
            'A bypassed invest must not lock AP either — otherwise dev mode still runs the pool dry.'
        );
    }

    public function test_invest(): void
    {
        // With real AdvisorService: engineer level=9 -> totalAP=50, no locked AP -> availableAP=50
        // infirmary ap_spend already=10 (max), invest returns true (no effective change but succeeds)
        $result = $this->service->invest($this->colonyId, $this->entityId, 'add', 1);
        $this->assertTrue($result);

        $this->expectException(\InvalidArgumentException::class);
        $this->service->invest(-1, $this->entityId);
    }

    /**
     * Supply enforcement: levelup is blocked when free supply < supply_cost.
     *
     * Setup: zero all supply_costs, set infirmary supply_cost=3.
     * Colony 1 infirmary is level=3 → uses 9 supply.
     * cap=100 → free=91 → levelup passes.
     * After levelup (level=4): uses 12; cap=11 → free=0 < 3 → blocked.
     */
    public function test_levelup_blocked_when_insufficient_supply(): void
    {
        config(['game.bypass.supply_checks' => false]);

        // infirmary.max_level is now capped at 3 (2026-08-25, Ausbaustufen-Umstellung)
        // and colony 1's infirmary fixture is already at level 3 — this test drives a
        // 3→4 levelup purely to exercise the supply gate, so lift the cap locally.
        DB::table('buildings')->where('id', $this->entityId)->update(['max_level' => null]);

        // Clear all supply costs, then set infirmary=3
        DB::table('buildings')->update(['supply_cost' => 0]);
        DB::table('ships')->update(['supply_cost' => 0]);
        DB::table('researches')->update(['supply_cost' => 0]);
        DB::table('buildings')->where('id', $this->entityId)->update(['supply_cost' => 3]);

        // Level=3 × 3 = 9 supply used; cap=100 → free=91 ≥ 3 → should pass
        DB::table('user_resources')->where('user_id', 3)->update(['supply' => 100]);
        $this->assertTrue($this->service->levelup($this->colonyId, $this->entityId));

        // Reset ap_spend after levelup (now level=4); re-prep AP for next levelup
        DB::table('colony_buildings')
            ->where(['colony_id' => $this->colonyId, 'building_id' => $this->entityId])
            ->update(['ap_spend' => 10]);

        // Now infirmary is level=4 → uses 12 supply; cap=11 → free=max(0,11-12)=0 < 3 → blocked
        DB::table('user_resources')->where('user_id', 3)->update(['supply' => 11]);
        $this->assertFalse($this->service->levelup($this->colonyId, $this->entityId));
    }

    public function test_levelup_allowed_when_supply_bypassed(): void
    {
        // supply_checks bypassed → levelup succeeds regardless of supply
        config(['game.bypass.supply_checks' => true]);

        // infirmary.max_level is now capped at 3 (2026-08-25, Ausbaustufen-Umstellung)
        // and colony 1's infirmary fixture is already at level 3 — this test is about
        // the supply bypass, not the tier cap, so lift the cap locally.
        DB::table('buildings')->where('id', $this->entityId)->update(['max_level' => null]);

        DB::table('buildings')->update(['supply_cost' => 999]);
        DB::table('user_resources')->where('user_id', 3)->update(['supply' => 0]);

        $result = $this->service->levelup($this->colonyId, $this->entityId);
        $this->assertTrue($result);
    }
}
