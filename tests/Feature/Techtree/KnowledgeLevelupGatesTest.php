<?php

namespace Tests\Feature\Techtree;

use App\Enums\BuildingId;
use App\Models\User;
use App\Services\Techtree\BuildingPrerequisites;
use App\Services\Techtree\ResearchService;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Lab-side knowledge gates (follow-up to silent research, owner 2026-11-11):
 * the top level comes from config/knowledge.php levelup_costs, and the second building
 * prerequisite (e.g. cartography → hangar) is checked by one shared rule for the
 * techtree display, the lab level-up and silent research.
 *
 * Fixture colony 1: CC Lv3, sciencelab Lv1, hangar Lv3.
 */
class KnowledgeLevelupGatesTest extends TestCase
{
    use RefreshDatabase;

    private const COLONY_ID = 1;

    private const CONSTRUCTION = 90;

    private const CARTOGRAPHY = 91;

    private ResearchService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(TestSeeder::class)->run();
        config(['game.knowledge_ap_cap_per_sol.base' => null]);
        $this->service = $this->app->make(ResearchService::class);
    }

    private function setKnowledge(int $id, int $level, int $apSpend): void
    {
        DB::table('colony_researches')->updateOrInsert(
            ['colony_id' => self::COLONY_ID, 'research_id' => $id],
            ['level' => $level, 'ap_spend' => $apSpend, 'status_points' => 20],
        );
    }

    private function level(int $id): int
    {
        return (int) DB::table('colony_researches')
            ->where('colony_id', self::COLONY_ID)->where('research_id', $id)->value('level');
    }

    private function ccLevel(int $level): void
    {
        DB::table('colony_buildings')
            ->where('colony_id', self::COLONY_ID)
            ->where('building_id', BuildingId::CommandCenter->value)
            ->update(['level' => $level]);
    }

    // ── 1: top level ─────────────────────────────────────────────────────────

    public function test_lab_levelup_is_refused_at_the_configured_top_level(): void
    {
        $top = ResearchService::maxKnowledgeLevel(self::CONSTRUCTION);
        $this->ccLevel(5);
        $this->setKnowledge(self::CONSTRUCTION, $top, 999);

        $this->assertSame('max_level', $this->service->levelupBlocker(self::COLONY_ID, self::CONSTRUCTION));
        $this->assertFalse($this->service->levelup(self::COLONY_ID, self::CONSTRUCTION));
        $this->assertSame($top, $this->level(self::CONSTRUCTION));
    }

    public function test_top_level_follows_the_cost_curve_not_a_constant(): void
    {
        config(['knowledge.construction.levelup_costs' => [1 => 20, 2 => 28]]);
        $this->setKnowledge(self::CONSTRUCTION, 2, 0);

        $this->assertSame(2, ResearchService::maxKnowledgeLevel(self::CONSTRUCTION));
        $this->assertSame('max_level', $this->service->levelupBlocker(self::COLONY_ID, self::CONSTRUCTION));
    }

    public function test_lab_invest_is_refused_at_the_top_level(): void
    {
        $this->ccLevel(5);
        $this->setKnowledge(self::CONSTRUCTION, ResearchService::maxKnowledgeLevel(self::CONSTRUCTION), 0);

        $this->assertSame('max_level', $this->service->investBlocker(self::COLONY_ID, self::CONSTRUCTION, 'add', 1));
        $this->assertFalse($this->service->invest(self::COLONY_ID, self::CONSTRUCTION, 'add', 1));
        $this->assertSame(0, (int) DB::table('colony_researches')
            ->where('colony_id', self::COLONY_ID)->where('research_id', self::CONSTRUCTION)->value('ap_spend'));
    }

    public function test_levelup_cost_beyond_the_top_level_is_zero_not_the_db_fallback(): void
    {
        $this->setKnowledge(self::CONSTRUCTION, ResearchService::maxKnowledgeLevel(self::CONSTRUCTION), 0);

        $this->assertSame(0, $this->service->knowledgeLevelupCost(self::COLONY_ID, self::CONSTRUCTION, 3));
    }

    public function test_techtree_reports_the_knowledge_top_level(): void
    {
        DB::table('advisors')->where('colony_id', self::COLONY_ID)->delete();
        Sanctum::actingAs(User::find(3));

        $item = collect($this->getJson('/api/v1/techtree')->assertOk()->json('phases'))
            ->flatMap(fn ($p) => $p['items'])
            ->first(fn ($i) => $i['type'] === 'research' && $i['id'] === self::CONSTRUCTION);

        $this->assertSame(ResearchService::maxKnowledgeLevel(self::CONSTRUCTION), $item['max_level']);
    }

    // ── 2: second building prerequisite ──────────────────────────────────────

    public function test_lab_levelup_checks_the_second_building_prerequisite(): void
    {
        DB::table('colony_buildings')
            ->where('colony_id', self::COLONY_ID)
            ->where('building_id', BuildingId::Hangar->value)
            ->delete();
        $threshold = $this->service->knowledgeLevelupCost(self::COLONY_ID, self::CARTOGRAPHY);
        $this->setKnowledge(self::CARTOGRAPHY, 0, $threshold);

        $this->assertSame('requires_building', $this->service->levelupBlocker(self::COLONY_ID, self::CARTOGRAPHY));
        $this->assertFalse($this->service->levelup(self::COLONY_ID, self::CARTOGRAPHY));
        $this->assertSame(0, $this->level(self::CARTOGRAPHY));
    }

    public function test_lab_levelup_passes_with_both_prerequisites(): void
    {
        $threshold = $this->service->knowledgeLevelupCost(self::COLONY_ID, self::CARTOGRAPHY);
        $this->setKnowledge(self::CARTOGRAPHY, 0, $threshold);

        $this->assertTrue($this->service->levelup(self::COLONY_ID, self::CARTOGRAPHY));
        $this->assertSame(1, $this->level(self::CARTOGRAPHY));
    }

    public function test_shared_rule_checks_both_slots_and_honours_waived_buildings(): void
    {
        $entity = (object) [
            'required_building_id' => 31, 'required_building_level' => 2,
            'required_building2_id' => 44, 'required_building2_level' => 1,
        ];
        $levels = [31 => 1, 44 => 1];
        $levelOf = fn (int $id): int => $levels[$id] ?? 0;

        $this->assertFalse(BuildingPrerequisites::met($entity, $levelOf));
        $this->assertTrue(BuildingPrerequisites::met($entity, $levelOf, waive: [31]));
        $this->assertFalse(BuildingPrerequisites::met($entity, fn (int $id): int => $id === 31 ? 2 : 0));
        $this->assertTrue(BuildingPrerequisites::met((array) $entity, fn (int $id): int => 2));
        $this->assertTrue(BuildingPrerequisites::met(['required_building_id' => null], $levelOf));
    }
}
