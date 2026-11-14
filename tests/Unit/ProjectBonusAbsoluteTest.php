<?php

namespace Tests\Unit;

use App\Services\ProjectBonusService;
use App\Services\TickService;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * AP scale spec 2026-11-13 §4/§5.1: discounts are absolute AP, additive; small costs (below
 * game.min_discountable_cost_ap) get no permanent discount; floors are absolute (projects
 * game.project_min_cost_ap, actions game.action_min_cost_ap).
 */
class ProjectBonusAbsoluteTest extends TestCase
{
    use RefreshDatabase;

    private const COLONY_ID = 1;

    private ProjectBonusService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(TestSeeder::class)->run();
        $this->app->instance(TickService::class, new TickService(10));
        config([
            'game.min_discountable_cost_ap' => 6,
            'game.project_min_cost_ap' => 2,
            'game.action_min_cost_ap' => 1,
            'knowledge.construction.ap_cost_reduction_per_lv' => [2 => 1, 4 => 1],
            'buildings.sciencelab.knowledge_ap_cost_reduction_per_lv' => [3 => 1, 5 => 1],
            'knowledge.cartography.mission_ap_reduction' => ['from_level' => 2, 'min_distance' => 3, 'ap' => 1],
            'missions.ap_by_distance' => [1 => 1, 2 => 1, 3 => 2, 4 => 3, 5 => 3],
        ]);
        DB::table('colony_building_discount_vouchers')->where('colony_id', self::COLONY_ID)->delete();
        $this->service = $this->app->make(ProjectBonusService::class);
    }

    private function setKnowledgeLevel(int $researchId, int $level): void
    {
        DB::table('colony_researches')->updateOrInsert(
            ['colony_id' => self::COLONY_ID, 'research_id' => $researchId],
            ['level' => $level, 'ap_spend' => 0, 'status_points' => 20]
        );
    }

    private function setSciencelab(int $level): void
    {
        DB::table('colony_buildings')->updateOrInsert(
            ['colony_id' => self::COLONY_ID, 'building_id' => 31, 'instance_id' => 1],
            ['level' => $level, 'status_points' => 20, 'ap_spend' => 0]
        );
    }

    private function addVoucher(int $ap): void
    {
        DB::table('colony_building_discount_vouchers')->insert([
            'colony_id' => self::COLONY_ID,
            'source' => 'information_broker',
            'discount_pct' => $ap,
            'granted_tick' => 5,
            'expires_tick' => null,
            'consumed_tick' => null,
        ]);
    }

    public function test_apply_discount_is_absolute_with_floor(): void
    {
        $this->assertSame(7, ProjectBonusService::applyDiscount(8, 1, 2));
        $this->assertSame(2, ProjectBonusService::applyDiscount(4, 3, 2));
        $this->assertSame(1, ProjectBonusService::applyDiscount(1, 1, 2), 'a cost below the floor is never raised');
    }

    public function test_construction_discount_sums_absolute_ap(): void
    {
        $this->setKnowledgeLevel(90, 2);
        $this->assertSame(1, $this->service->buildingApDiscount(self::COLONY_ID));

        $this->setKnowledgeLevel(90, 5);
        $this->assertSame(2, $this->service->buildingApDiscount(self::COLONY_ID));
    }

    public function test_construction_discount_skips_small_costs(): void
    {
        $this->setKnowledgeLevel(90, 4);

        $this->assertSame(4, $this->service->effectiveApForLevelup(self::COLONY_ID, 4));
        $this->assertSame(14, $this->service->effectiveApForLevelup(self::COLONY_ID, 16));
    }

    public function test_voucher_is_absolute_and_applies_to_small_costs_down_to_the_floor(): void
    {
        $this->addVoucher(2);
        $this->assertSame(2, $this->service->effectiveApForLevelup(self::COLONY_ID, 4));
        $this->assertSame(6, $this->service->effectiveApForLevelup(self::COLONY_ID, 8));

        DB::table('colony_building_discount_vouchers')->where('colony_id', self::COLONY_ID)->update(['discount_pct' => 3]);
        $this->assertSame(2, $this->service->effectiveApForLevelup(self::COLONY_ID, 4), 'never below the project floor');
    }

    public function test_sciencelab_discount_on_knowledge_is_absolute(): void
    {
        $this->setSciencelab(2);
        $this->assertSame(8, $this->service->effectiveKnowledgeApForLevelup(self::COLONY_ID, 8));

        $this->setSciencelab(3);
        $this->assertSame(7, $this->service->effectiveKnowledgeApForLevelup(self::COLONY_ID, 8));

        $this->setSciencelab(5);
        $this->assertSame(19, $this->service->effectiveKnowledgeApForLevelup(self::COLONY_ID, 21));
    }

    public function test_mission_base_cost_comes_from_distance_table(): void
    {
        $this->assertSame([1, 1, 2, 3, 3], array_map(fn ($d) => $this->service->effectiveMissionApCost(self::COLONY_ID, $d), [1, 2, 3, 4, 5]));
        $this->assertSame(3, ProjectBonusService::missionApBase(7), 'beyond the table the last entry holds');
    }

    public function test_cartography_cuts_long_missions_only(): void
    {
        $this->setKnowledgeLevel(91, 1);
        $this->assertSame(2, $this->service->effectiveMissionApCost(self::COLONY_ID, 3));

        $this->setKnowledgeLevel(91, 2);
        $this->assertSame(1, $this->service->effectiveMissionApCost(self::COLONY_ID, 2));
        $this->assertSame(1, $this->service->effectiveMissionApCost(self::COLONY_ID, 3));
        $this->assertSame(2, $this->service->effectiveMissionApCost(self::COLONY_ID, 5));
    }
}
