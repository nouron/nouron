<?php

namespace Tests\Unit;

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

    public function test_discount_sums_all_three_domains_additively(): void
    {
        // construction id=90, cartography id=91, trade id=95 (config/knowledge.php)
        $this->setKnowledgeLevel(90, 3);   // cumulative [2,4,4] = 10
        $this->setKnowledgeLevel(91, 1);   // cumulative [2] = 2
        $this->setKnowledgeLevel(95, 5);   // cumulative [2,4,4,3,2] = 15
        $service = $this->app->make(ProjectBonusService::class);

        $this->assertSame(27, $service->buildingApDiscountPercent(self::COLONY_ID));
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
}
