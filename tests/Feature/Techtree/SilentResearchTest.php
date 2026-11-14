<?php

namespace Tests\Feature\Techtree;

use App\Enums\BuildingId;
use App\Services\AdvisorService;
use App\Services\DailyApCapService;
use App\Services\Techtree\KnowledgeBonusResult;
use App\Services\Techtree\ResearchService;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * "Stille Forschung" (spec 2026-11-11-tomas-rat-rueckmeldung §5, sentences 1–12):
 * earmarked bonus AP (ResearchService::investBonus) level a knowledge up on their own,
 * with or without a sciencelab; all other gates (CC level, max level, techtree
 * prerequisites besides the lab) still apply and nothing is ever dropped silently.
 *
 * Fixture colony 1 (Springfield): CC Lv3, sciencelab Lv1, hangar Lv3.
 */
class SilentResearchTest extends TestCase
{
    use RefreshDatabase;

    private const COLONY_ID = 1;

    private const CONSTRUCTION = 90;   // no second building prerequisite

    private const CARTOGRAPHY = 91;    // needs a hangar (required_building2_id = 44)

    private ResearchService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(TestSeeder::class)->run();
        $this->service = $this->app->make(ResearchService::class);
    }

    private function removeLab(): void
    {
        DB::table('colony_buildings')
            ->where('colony_id', self::COLONY_ID)
            ->where('building_id', BuildingId::Sciencelab->value)
            ->delete();
    }

    private function setKnowledge(int $id, int $level, int $apSpend): void
    {
        DB::table('colony_researches')->updateOrInsert(
            ['colony_id' => self::COLONY_ID, 'research_id' => $id],
            ['level' => $level, 'ap_spend' => $apSpend, 'status_points' => 20],
        );
    }

    /** AP threshold to reach $level + 1, measured through the service (includes discounts). */
    private function thresholdFrom(int $id, int $level): int
    {
        $this->setKnowledge($id, $level, 0);

        return $this->service->knowledgeLevelupCost(self::COLONY_ID, $id);
    }

    /** @return array{level: int, ap_spend: int} */
    private function state(int $id): array
    {
        $row = DB::table('colony_researches')
            ->where('colony_id', self::COLONY_ID)
            ->where('research_id', $id)
            ->first();

        return ['level' => (int) ($row->level ?? 0), 'ap_spend' => (int) ($row->ap_spend ?? 0)];
    }

    private function setCcLevel(int $level): void
    {
        DB::table('colony_buildings')
            ->where('colony_id', self::COLONY_ID)
            ->where('building_id', BuildingId::CommandCenter->value)
            ->update(['level' => $level]);
    }

    // ── 1–3: level up on reaching the threshold ──────────────────────────────

    public function test_bonus_reaching_threshold_without_lab_levels_the_knowledge_up(): void
    {
        $this->removeLab();
        $t1 = $this->thresholdFrom(self::CONSTRUCTION, 0);

        $result = $this->service->investBonus(self::COLONY_ID, self::CONSTRUCTION, $t1);

        $this->assertInstanceOf(KnowledgeBonusResult::class, $result);
        $this->assertTrue($result->accepted);
        $this->assertSame(['level' => 1, 'ap_spend' => 0], $this->state(self::CONSTRUCTION));
        $this->assertSame(0, $result->levelBefore);
        $this->assertSame(1, $result->levelAfter);
        $this->assertTrue($result->leveledUp());
        $this->assertSame($t1, $result->apInvested);
        $this->assertSame(0, $result->apForfeited);
    }

    public function test_bonus_reaching_threshold_with_lab_levels_up_as_well(): void
    {
        $t1 = $this->thresholdFrom(self::CONSTRUCTION, 0);

        $result = $this->service->investBonus(self::COLONY_ID, self::CONSTRUCTION, $t1);

        $this->assertTrue($result->leveledUp());
        $this->assertSame(['level' => 1, 'ap_spend' => 0], $this->state(self::CONSTRUCTION));
    }

    public function test_bonus_below_threshold_only_adds_progress(): void
    {
        $this->removeLab();
        $t1 = $this->thresholdFrom(self::CONSTRUCTION, 0);

        $result = $this->service->investBonus(self::COLONY_ID, self::CONSTRUCTION, $t1 - 1);

        $this->assertFalse($result->leveledUp());
        $this->assertSame(['level' => 0, 'ap_spend' => $t1 - 1], $this->state(self::CONSTRUCTION));
        $this->assertSame($t1 - 1, $result->apInvested);
    }

    public function test_a_full_bar_from_before_levels_up_on_the_next_bonus(): void
    {
        $this->removeLab();
        $t1 = $this->thresholdFrom(self::CONSTRUCTION, 0);
        $this->setKnowledge(self::CONSTRUCTION, 0, $t1);

        $result = $this->service->investBonus(self::COLONY_ID, self::CONSTRUCTION, 2);

        $this->assertSame(['level' => 1, 'ap_spend' => 2], $this->state(self::CONSTRUCTION));
        $this->assertSame(2, $result->apInvested);
    }

    public function test_an_overfull_bar_carries_its_surplus_into_the_next_level(): void
    {
        $this->removeLab();
        $t1 = $this->thresholdFrom(self::CONSTRUCTION, 0);
        $this->setKnowledge(self::CONSTRUCTION, 0, $t1 + 2); // e.g. a discount lowered the threshold

        $result = $this->service->investBonus(self::COLONY_ID, self::CONSTRUCTION, 3);

        $this->assertSame(['level' => 1, 'ap_spend' => 5], $this->state(self::CONSTRUCTION));
        $this->assertSame(3, $result->apInvested);
    }

    // ── 4–5: refusals ────────────────────────────────────────────────────────

    public function test_bonus_is_refused_when_the_cc_gate_blocks_the_next_level(): void
    {
        $this->setCcLevel(3);
        $this->setKnowledge(self::CONSTRUCTION, 3, 5);

        $result = $this->service->investBonus(self::COLONY_ID, self::CONSTRUCTION, 4);

        $this->assertFalse($result->accepted);
        $this->assertSame('knowledge_cc_gate', $result->blockReason);
        $this->assertSame(0, $result->apInvested);
        $this->assertSame(['level' => 3, 'ap_spend' => 5], $this->state(self::CONSTRUCTION));
    }

    public function test_bonus_is_refused_at_max_level(): void
    {
        $this->setCcLevel(5);
        $this->setKnowledge(self::CONSTRUCTION, 5, 0);

        $result = $this->service->investBonus(self::COLONY_ID, self::CONSTRUCTION, 4);

        $this->assertFalse($result->accepted);
        $this->assertSame('max_level', $result->blockReason);
        $this->assertSame(['level' => 5, 'ap_spend' => 0], $this->state(self::CONSTRUCTION));
    }

    // ── 6–8: carry-over and forfeit ──────────────────────────────────────────

    public function test_rest_carries_into_the_open_next_level(): void
    {
        $this->removeLab();
        $t1 = $this->thresholdFrom(self::CONSTRUCTION, 0);

        $result = $this->service->investBonus(self::COLONY_ID, self::CONSTRUCTION, $t1 + 3);

        $this->assertSame(['level' => 1, 'ap_spend' => 3], $this->state(self::CONSTRUCTION));
        $this->assertSame($t1 + 3, $result->apInvested);
        $this->assertSame(0, $result->apForfeited);
        $this->assertNull($result->blockReason);
    }

    public function test_rest_is_forfeited_with_reason_when_the_next_level_is_blocked(): void
    {
        $this->setCcLevel(3);
        $t3 = $this->thresholdFrom(self::CONSTRUCTION, 2);
        $this->setKnowledge(self::CONSTRUCTION, 2, $t3 - 2);

        $result = $this->service->investBonus(self::COLONY_ID, self::CONSTRUCTION, 5);

        $this->assertTrue($result->accepted);
        $this->assertSame(['level' => 3, 'ap_spend' => 0], $this->state(self::CONSTRUCTION));
        $this->assertSame(2, $result->apInvested);
        $this->assertSame(3, $result->apForfeited);
        $this->assertSame('knowledge_cc_gate', $result->blockReason);
    }

    public function test_one_bonus_can_cross_several_thresholds(): void
    {
        $this->removeLab();
        $t1 = $this->thresholdFrom(self::CONSTRUCTION, 0);
        $t2 = $this->thresholdFrom(self::CONSTRUCTION, 1);
        $this->setKnowledge(self::CONSTRUCTION, 0, 0);

        $result = $this->service->investBonus(self::COLONY_ID, self::CONSTRUCTION, $t1 + $t2 + 4);

        $this->assertSame(['level' => 2, 'ap_spend' => 4], $this->state(self::CONSTRUCTION));
        $this->assertSame(0, $result->levelBefore);
        $this->assertSame(2, $result->levelAfter);
    }

    public function test_several_thresholds_stop_at_the_gate_and_forfeit_the_rest(): void
    {
        $this->setCcLevel(3);
        $t2 = $this->thresholdFrom(self::CONSTRUCTION, 1);
        $t3 = $this->thresholdFrom(self::CONSTRUCTION, 2);
        $this->setKnowledge(self::CONSTRUCTION, 1, 0);

        $result = $this->service->investBonus(self::COLONY_ID, self::CONSTRUCTION, $t2 + $t3 + 10);

        $this->assertSame(['level' => 3, 'ap_spend' => 0], $this->state(self::CONSTRUCTION));
        $this->assertSame($t2 + $t3, $result->apInvested);
        $this->assertSame(10, $result->apForfeited);
        $this->assertSame('knowledge_cc_gate', $result->blockReason);
    }

    // ── 9: result payload ────────────────────────────────────────────────────

    public function test_result_serialises_for_the_frontend(): void
    {
        $this->removeLab();
        $t1 = $this->thresholdFrom(self::CONSTRUCTION, 0);

        $array = $this->service->investBonus(self::COLONY_ID, self::CONSTRUCTION, $t1 + 1)->toArray();

        $this->assertSame(self::CONSTRUCTION, $array['knowledge_id']);
        $this->assertSame('construction', $array['knowledge_key']);
        $this->assertSame($t1 + 1, $array['ap_invested']);
        $this->assertSame(1, $array['level']);
        $this->assertTrue($array['leveled_up']);
        $this->assertSame(0, $array['ap_forfeited']);
        $this->assertNull($array['block_reason']);
    }

    // ── 10: no pool, no lab cap ──────────────────────────────────────────────

    public function test_bonus_touches_neither_shared_pool_nor_lab_cap(): void
    {
        $advisors = $this->app->make(AdvisorService::class);
        $before = $advisors->getAvailableActionPoints(self::COLONY_ID);
        $t1 = $this->thresholdFrom(self::CONSTRUCTION, 0);

        $this->service->investBonus(self::COLONY_ID, self::CONSTRUCTION, $t1 + 2);

        $this->assertSame($before, $advisors->getAvailableActionPoints(self::COLONY_ID));
        $this->assertSame(0, $this->app->make(DailyApCapService::class)
            ->spent(self::COLONY_ID, ResearchService::capKey(self::CONSTRUCTION)));
    }

    // ── 12: shared "selectable" query ────────────────────────────────────────

    public function test_selectable_ignores_the_lab_but_keeps_other_prerequisites(): void
    {
        $this->removeLab();
        $this->assertNull($this->service->bonusTargetBlocker(self::COLONY_ID, self::CONSTRUCTION));

        DB::table('colony_buildings')
            ->where('colony_id', self::COLONY_ID)
            ->where('building_id', BuildingId::Hangar->value)
            ->delete();
        $this->assertSame('requires_building', $this->service->bonusTargetBlocker(self::COLONY_ID, self::CARTOGRAPHY));
    }

    public function test_requires_building_refuses_the_bonus(): void
    {
        DB::table('colony_buildings')
            ->where('colony_id', self::COLONY_ID)
            ->where('building_id', BuildingId::Hangar->value)
            ->delete();

        $result = $this->service->investBonus(self::COLONY_ID, self::CARTOGRAPHY, 3);

        $this->assertFalse($result->accepted);
        $this->assertSame('requires_building', $result->blockReason);
        $this->assertSame(0, $this->state(self::CARTOGRAPHY)['ap_spend']);
    }

    public function test_non_knowledge_is_not_selectable(): void
    {
        $this->assertSame('entity_not_found', $this->service->bonusTargetBlocker(self::COLONY_ID, 9901));
    }

    // ── rough progress (A8) ──────────────────────────────────────────────────

    public function test_rough_progress_band_splits_the_bar_into_three(): void
    {
        config(['game.knowledge_rough_progress_bands' => [0.34, 0.67]]);

        $this->assertNull($this->service->roughProgressBand(0, 20));
        $this->assertSame(1, $this->service->roughProgressBand(1, 20));
        $this->assertSame(1, $this->service->roughProgressBand(6, 20));
        $this->assertSame(2, $this->service->roughProgressBand(7, 20));
        $this->assertSame(2, $this->service->roughProgressBand(13, 20));
        $this->assertSame(3, $this->service->roughProgressBand(14, 20));
        $this->assertSame(3, $this->service->roughProgressBand(20, 20));
        $this->assertNull($this->service->roughProgressBand(5, 0));
    }
}
