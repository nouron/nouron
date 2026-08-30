<?php

namespace App\Services;

use App\Console\Commands\GameTick;
use App\Enums\BuildingId;
use Illuminate\Support\Facades\DB;

/**
 * Additive AP-cost discounts for building projects (GDD §13.3). Currently sums the
 * two "domain knowledge" curves (construction, trade) — advisor-rank and CC-level
 * bonus sources from the same GDD table are not yet implemented (out of scope for
 * this plan, see docs/superpowers/specs/2026-08-15-knowledge-effects-and-
 * encounters-design.md §2). Also hosts a second, independent discount pool for
 * Kenntnis-Levelup-AP-Kosten, sourced from the Analytik-Labor (sciencelab) building
 * level — see knowledgeApDiscountPercent() below. Since 2026-08-27, cartography no
 * longer contributes to the building-discount pool above — it has its own separate
 * Navigation-AP discount pool instead, see effectiveNavigationApCost() below.
 */
class ProjectBonusService
{
    /** research_id values from config/knowledge.php that discount building projects. */
    private const DOMAIN_KNOWLEDGE_KEYS = ['construction', 'trade'];

    public function buildingApDiscountPercent(int $colonyId): int
    {
        $total = 0;

        foreach (self::DOMAIN_KNOWLEDGE_KEYS as $key) {
            $cfg = config("knowledge.{$key}");
            $researchId = (int) $cfg['id'];
            $curve = $cfg['ap_cost_reduction_per_lv'] ?? [];

            $level = (int) DB::table('colony_researches')
                ->where('colony_id', $colonyId)
                ->where('research_id', $researchId)
                ->value('level');

            $total += GameTick::cumulativeCurveYield($curve, $level);
        }

        return $total;
    }

    public function effectiveApForLevelup(int $colonyId, int $baseApForLevelup): int
    {
        $discountPercent = $this->buildingApDiscountPercent($colonyId);
        $minCostFactor = (float) config('game.project_min_cost_factor', 0.5);

        return self::applyDiscount($baseApForLevelup, $discountPercent, $minCostFactor);
    }

    /**
     * Additive AP-cost discount for Kenntnis-Levelups, sourced from the
     * Analytik-Labor (sciencelab) building level — a separate, independent pool
     * from buildingApDiscountPercent() above. Only levels 4-5 carry an effect
     * (levels 1-3 remain pure knowledge-gate thresholds, no discount of their
     * own — see docs/superpowers/plans/2026-08-26-building-tier-foundation.md).
     */
    public function knowledgeApDiscountPercent(int $colonyId): int
    {
        $level = (int) (DB::table('colony_buildings')
            ->where('colony_id', $colonyId)
            ->where('building_id', BuildingId::Sciencelab->value)
            ->value('level') ?? 0);

        $curve = config('buildings.sciencelab.knowledge_ap_cost_reduction_per_lv', []);

        return GameTick::cumulativeCurveYield($curve, $level);
    }

    public function effectiveKnowledgeApForLevelup(int $colonyId, int $baseApForLevelup): int
    {
        $discountPercent = $this->knowledgeApDiscountPercent($colonyId);
        $minCostFactor = (float) config('game.project_min_cost_factor', 0.5);

        return self::applyDiscount($baseApForLevelup, $discountPercent, $minCostFactor);
    }

    /**
     * Additive AP-cost discount for Navigation actions (currently: tile
     * exploration, ColonyTileService::exploreTile()), sourced from the
     * cartography knowledge level — a separate, independent pool from
     * buildingApDiscountPercent() above. cartography no longer contributes
     * to the building-project pool (Owner-Entscheidung 2026-08-27).
     */
    public function navigationApDiscountPercent(int $colonyId): int
    {
        $level = (int) DB::table('colony_researches')
            ->where('colony_id', $colonyId)
            ->where('research_id', (int) config('knowledge.cartography.id'))
            ->value('level');

        $curve = config('knowledge.cartography.nav_ap_reduction_per_lv', []);

        return GameTick::cumulativeCurveYield($curve, $level);
    }

    public function effectiveNavigationApCost(int $colonyId, int $baseApCost): int
    {
        $discountPercent = $this->navigationApDiscountPercent($colonyId);
        $minCostFactor = (float) config('game.project_min_cost_factor', 0.5);

        return self::applyDiscount($baseApCost, $discountPercent, $minCostFactor);
    }

    /** Pure discount math, factored out so the floor logic is testable without DB state. */
    public static function applyDiscount(int $base, int $discountPercent, float $minCostFactor): int
    {
        $floor = (int) ceil($base * $minCostFactor);
        $discounted = (int) round($base * (1 - $discountPercent / 100));

        return max($floor, $discounted);
    }
}
