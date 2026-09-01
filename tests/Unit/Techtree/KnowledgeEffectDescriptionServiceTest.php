<?php

namespace Tests\Unit\Techtree;

use App\Services\Techtree\KnowledgeEffectDescriptionService;
use Tests\TestCase;

/**
 * KnowledgeEffectDescriptionService — Owner-Playtest-Fund 2026-08-31
 * (follow-up to BuildingUnlockService): buildings show what the NEXT level
 * unlocks via a pure data lookup (required_building_id/level). Knowledge
 * levels have no such discrete unlock — their effect is a continuous curve
 * (config/knowledge.php, config/game.php `*_per_lv`/`*_per_level` arrays,
 * from the Kenntnis-Effekte-Redesign series). This translates the curve
 * VALUE AT a given level into a short readable line, e.g. "construction Lv3"
 * → "-4% Bau-AP-Kosten" (config/knowledge.php: ap_cost_reduction_per_lv[3]=4).
 * Levels whose curve entry is 0 (no change at that level) produce no line.
 */
class KnowledgeEffectDescriptionServiceTest extends TestCase
{
    private KnowledgeEffectDescriptionService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = $this->app->make(KnowledgeEffectDescriptionService::class);
    }

    public function test_construction_level3_reduces_construction_ap_cost(): void
    {
        // config/knowledge.php: construction.ap_cost_reduction_per_lv[3] = 4
        $lines = $this->service->effectsAtLevel('construction', 3);

        $this->assertContains('-4% Bau-AP-Kosten', $lines);
    }

    public function test_geology_level1_has_two_distinct_effects(): void
    {
        // config/game.php: geology_harvester_bonus_per_level[1]=3,
        // geology_instability_risk_reduction_per_lv[1]=3
        $lines = $this->service->effectsAtLevel('geology', 1);

        $this->assertContains('+3 Rg/Sol Harvester-Ertrag', $lines);
        $this->assertContains('-3% Instabilitäts-Risiko', $lines);
    }

    public function test_trade_level4_has_three_distinct_effects(): void
    {
        // config/knowledge.php trade: ap_cost_reduction_per_lv[4]=3,
        // bar_offer_boost_per_lv[4]=0 (must be OMITTED), trade_price_bonus_per_lv[4]=2
        $lines = $this->service->effectsAtLevel('trade', 4);

        $this->assertContains('-3% Bau-AP-Kosten', $lines);
        $this->assertContains('+2% Handelspreis-Bonus', $lines);
        $this->assertCount(2, $lines, 'bar_offer_boost_per_lv[4]=0 must not produce a line');
    }

    public function test_trade_level2_includes_the_bar_slot_effect(): void
    {
        // bar_offer_boost_per_lv[2]=1 — the one level where it's non-zero.
        $lines = $this->service->effectsAtLevel('trade', 2);

        $this->assertContains('+1 Bar-Angebotsslot', $lines);
    }

    public function test_defense_level_reduces_storm_risk(): void
    {
        $lines = $this->service->effectsAtLevel('defense', 2);

        $this->assertContains('-5% Sturm-Risiko', $lines);
    }

    public function test_health_level_reduces_plague_risk(): void
    {
        $lines = $this->service->effectsAtLevel('health', 3);

        $this->assertContains('-5% Seuchenausbruch-Risiko', $lines);
    }

    public function test_agronomy_level_increases_agrardom_yield(): void
    {
        $lines = $this->service->effectsAtLevel('agronomy', 2);

        $this->assertContains('+2 Or/Sol Agrardom-Ertrag', $lines);
    }

    public function test_cartography_level_reduces_navigation_ap_cost(): void
    {
        $lines = $this->service->effectsAtLevel('cartography', 1);

        $this->assertContains('-4% Navigations-AP-Kosten', $lines);
    }

    public function test_unknown_knowledge_key_returns_empty(): void
    {
        $this->assertSame([], $this->service->effectsAtLevel('does_not_exist', 1));
    }

    public function test_level_zero_returns_empty(): void
    {
        $this->assertSame([], $this->service->effectsAtLevel('construction', 0));
    }
}
