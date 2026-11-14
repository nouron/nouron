<?php

namespace Tests\Unit\Techtree;

use App\Services\Techtree\ResearchService;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * ResearchService::knowledgeLevelupCost() — Analytik-Labor-Domänen-Effizienzbonus
 * (Design-Spec 2026-08-23). Vor diesem Plan war knowledgeLevelupCost() der einzige
 * AP-Kosten-Pfad im Techtree-System ohne jede Rabatt-Anwendung.
 */
class ResearchServiceKnowledgeDiscountTest extends TestCase
{
    use RefreshDatabase;

    private const COLONY_ID = 1;

    private const SCIENCELAB_ID = 31;

    // cartography (id=91): required_building_id=31 (sciencelab) Lv1, kein
    // required_building2_id — sciencelab-Level lässt sich also frei setzen, ohne
    // ein zweites Gate zu berühren. levelup_costs[1]=20 (config/knowledge.php).
    private const CARTOGRAPHY_ID = 91;

    private ResearchService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(TestSeeder::class)->run();
        $this->service = $this->app->make(ResearchService::class);
    }

    private function setSciencelabLevel(int $level): void
    {
        DB::table('colony_buildings')->updateOrInsert(
            ['colony_id' => self::COLONY_ID, 'building_id' => self::SCIENCELAB_ID],
            ['level' => $level, 'status_points' => 20, 'ap_spend' => 0]
        );
    }

    public function test_knowledge_levelup_cost_is_undiscounted_at_sciencelab_level_1(): void
    {
        $this->setSciencelabLevel(1);

        $rawCost = (int) config('knowledge.cartography.levelup_costs.1');
        $cost = $this->service->knowledgeLevelupCost(self::COLONY_ID, self::CARTOGRAPHY_ID);

        $this->assertSame($rawCost, $cost, 'at Lv1, sciencelab must not discount knowledge levelup costs — pure gate threshold');
    }

    /** AP scale 2026-11-13 §4: the lab discount is absolute AP, first at lab level 3. */
    public function test_knowledge_levelup_cost_is_discounted_at_sciencelab_level_3(): void
    {
        $this->setSciencelabLevel(3);

        $rawCost = (int) config('knowledge.cartography.levelup_costs.1');
        $discount = (int) (config('buildings.sciencelab.knowledge_ap_cost_reduction_per_lv')[3] ?? 0);

        $cost = $this->service->knowledgeLevelupCost(self::COLONY_ID, self::CARTOGRAPHY_ID);

        $this->assertLessThan($rawCost, $cost, 'at Lv3, knowledge levelup cost must already be discounted below the raw config value');
        $this->assertSame($rawCost - $discount, $cost);
    }

    public function test_knowledge_levelup_cost_is_discounted_at_sciencelab_level_5(): void
    {
        $this->setSciencelabLevel(5);

        $rawCost = (int) config('knowledge.cartography.levelup_costs.1');
        $curve = config('buildings.sciencelab.knowledge_ap_cost_reduction_per_lv');
        $discount = (int) array_sum(array_intersect_key($curve, array_flip(range(1, 5))));

        $cost = $this->service->knowledgeLevelupCost(self::COLONY_ID, self::CARTOGRAPHY_ID);

        $this->assertLessThan($rawCost, $cost, 'at Lv5, knowledge levelup cost must be discounted below the raw config value');
        $this->assertSame(max((int) config('game.project_min_cost_ap'), $rawCost - $discount), $cost);
    }
}
