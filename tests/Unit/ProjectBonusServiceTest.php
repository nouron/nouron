<?php

namespace Tests\Unit;

use App\Console\Commands\GameTick;
use App\Services\ProjectBonusService;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ProjectBonusServiceTest extends TestCase
{
    use RefreshDatabase;

    private const COLONY_ID = 1;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(TestSeeder::class)->run();
    }

    private function setKnowledgeLevel(int $researchId, int $level): void
    {
        DB::table('colony_researches')->updateOrInsert(
            ['colony_id' => self::COLONY_ID, 'research_id' => $researchId],
            ['level' => $level, 'ap_spend' => 0, 'status_points' => 20]
        );
    }

    public function test_discount_is_zero_with_no_knowledge_invested(): void
    {
        $service = $this->app->make(ProjectBonusService::class);

        $this->assertSame(0, $service->buildingApDiscountPercent(self::COLONY_ID));
    }

    public function test_discount_sums_construction_and_trade_additively(): void
    {
        // construction id=90, trade id=95 (config/knowledge.php) — cartography (id=91)
        // ist NICHT mehr Teil dieses Pools (siehe navigationApDiscountPercent() unten).
        $this->setKnowledgeLevel(90, 3);   // cumulative [2,4,4] = 10
        $this->setKnowledgeLevel(95, 5);   // cumulative [2,4,4,3,2] = 15
        $service = $this->app->make(ProjectBonusService::class);

        $this->assertSame(25, $service->buildingApDiscountPercent(self::COLONY_ID));
    }

    public function test_cartography_no_longer_contributes_to_building_discount(): void
    {
        $this->setKnowledgeLevel(91, 5);   // cartography Lv5 — voll investiert
        $service = $this->app->make(ProjectBonusService::class);

        $this->assertSame(0, $service->buildingApDiscountPercent(self::COLONY_ID), 'cartography must no longer feed the building-project discount pool');
    }

    public function test_effective_ap_for_levelup_applies_discount(): void
    {
        $this->setKnowledgeLevel(90, 5);   // construction Lv5 = 15%
        $service = $this->app->make(ProjectBonusService::class);

        // 10 * (1 - 0.15) = 8.5 → round to 9 (round-half-up)
        $this->assertSame(9, $service->effectiveApForLevelup(self::COLONY_ID, 10));
    }

    /**
     * With current config, max additive discount is 45% (15% × 3 domains) — always
     * below the 50% floor, so it never binds via real knowledge levels (by design,
     * see spec §Global Constraints: the floor is a guard rail for future bonus
     * sources, not active yet). Test the floor logic directly via the pure helper
     * instead of trying to manufacture a >50% discount through the DB.
     */
    public function test_apply_discount_never_drops_below_min_cost_factor(): void
    {
        // 80% discount would give round(10 * 0.2) = 2, but the 0.5 floor caps it at 5.
        $this->assertSame(5, ProjectBonusService::applyDiscount(10, 80, 0.5));
    }

    public function test_apply_discount_rounds_half_up_below_the_floor(): void
    {
        // 15% discount on base 10: round(10 * 0.85) = round(8.5) = 9, floor = ceil(5) = 5.
        $this->assertSame(9, ProjectBonusService::applyDiscount(10, 15, 0.5));
    }

    // ── Analytik-Labor Domänen-Effizienzbonus "Wissen" (Design-Spec 2026-08-23) ──

    private const SCIENCELAB_ID = 31;

    private function setSciencelabLevel(int $level): void
    {
        DB::table('colony_buildings')->updateOrInsert(
            ['colony_id' => self::COLONY_ID, 'building_id' => self::SCIENCELAB_ID],
            ['level' => $level, 'status_points' => 20, 'ap_spend' => 0]
        );
    }

    public function test_knowledge_discount_is_zero_below_level_4(): void
    {
        $this->setSciencelabLevel(3);
        $service = $this->app->make(ProjectBonusService::class);

        $this->assertSame(0, $service->knowledgeApDiscountPercent(self::COLONY_ID));
    }

    public function test_knowledge_discount_at_level_4(): void
    {
        $this->setSciencelabLevel(4);
        $service = $this->app->make(ProjectBonusService::class);

        $expected = (int) (config('buildings.sciencelab.knowledge_ap_cost_reduction_per_lv')[4] ?? 0);
        $this->assertGreaterThan(0, $expected, 'precondition: config must define a Lv4 discount');
        $this->assertSame($expected, $service->knowledgeApDiscountPercent(self::COLONY_ID));
    }

    public function test_knowledge_discount_at_level_5_is_cumulative(): void
    {
        $this->setSciencelabLevel(5);
        $service = $this->app->make(ProjectBonusService::class);

        $curve = config('buildings.sciencelab.knowledge_ap_cost_reduction_per_lv');
        $expected = (int) (($curve[4] ?? 0) + ($curve[5] ?? 0));
        $this->assertSame($expected, $service->knowledgeApDiscountPercent(self::COLONY_ID));
    }

    public function test_effective_knowledge_ap_for_levelup_applies_discount(): void
    {
        $this->setSciencelabLevel(5);
        $service = $this->app->make(ProjectBonusService::class);

        $discountPercent = $service->knowledgeApDiscountPercent(self::COLONY_ID);
        $expected = ProjectBonusService::applyDiscount(100, $discountPercent, (float) config('game.project_min_cost_factor', 0.5));
        $this->assertSame($expected, $service->effectiveKnowledgeApForLevelup(self::COLONY_ID, 100));
    }

    public function test_knowledge_discount_does_not_affect_building_discount_pool(): void
    {
        // Die beiden Pools sind unabhängig — Analytik-Labor-Level darf den
        // bestehenden Gebäude-Rabatt (construction/trade) nicht
        // beeinflussen, und umgekehrt.
        $this->setSciencelabLevel(5);
        $this->setKnowledgeLevel(90, 0); // construction unbelegt
        $service = $this->app->make(ProjectBonusService::class);

        $this->assertSame(0, $service->buildingApDiscountPercent(self::COLONY_ID));
    }

    // ── cartography Navigation-AP-Rabatt (Owner-Entscheidung 2026-08-27) ────────

    public function test_navigation_discount_is_zero_with_no_cartography(): void
    {
        $service = $this->app->make(ProjectBonusService::class);

        $this->assertSame(0, $service->navigationApDiscountPercent(self::COLONY_ID));
    }

    public function test_navigation_discount_scales_with_cartography_level(): void
    {
        $this->setKnowledgeLevel(91, 3);
        $service = $this->app->make(ProjectBonusService::class);

        $curve = config('knowledge.cartography.nav_ap_reduction_per_lv');
        $expected = GameTick::cumulativeCurveYield($curve, 3);
        $this->assertGreaterThan(0, $expected, 'precondition: config must define a non-zero curve up to Lv3');
        $this->assertSame($expected, $service->navigationApDiscountPercent(self::COLONY_ID));
    }

    public function test_effective_navigation_ap_cost_applies_discount(): void
    {
        $this->setKnowledgeLevel(91, 5);
        $service = $this->app->make(ProjectBonusService::class);

        $discountPercent = $service->navigationApDiscountPercent(self::COLONY_ID);
        $expected = ProjectBonusService::applyDiscount(10, $discountPercent, (float) config('game.project_min_cost_factor', 0.5));

        $this->assertLessThan(10, $expected, 'precondition: discount must actually lower the base cost of 10');
        $this->assertSame($expected, $service->effectiveNavigationApCost(self::COLONY_ID, 10));
    }
}
