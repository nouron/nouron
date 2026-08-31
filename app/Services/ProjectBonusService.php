<?php

namespace App\Services;

use App\Console\Commands\GameTick;
use App\Enums\BuildingId;
use Illuminate\Support\Facades\DB;

/**
 * Hosts several independent AP-cost discount and price-bonus pools (GDD §13.3),
 * each sourced from its own knowledge/building level and combined additively
 * within its own pool only — no cross-pool stacking:
 *   - building-project AP discount, summed across the construction and trade
 *     knowledge curves (buildingApDiscountPercent());
 *   - knowledge-levelup AP discount, sourced from the Analytik-Labor
 *     (sciencelab) building level (knowledgeApDiscountPercent());
 *   - navigation-AP discount for exploration and hangar mission actions,
 *     sourced from the cartography knowledge level (navigationApDiscountPercent());
 *   - trade-price bonus across all 3 trade channels, sourced from the trade
 *     knowledge level (tradePriceBonusPercent()).
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
     * Additive AP-cost discount for Navigation actions — tile exploration
     * (ColonyTileService::exploreTile()) and hangar missions
     * (HangarService::dispatchShip(), HangarService::getMissionCatalogFor()) —
     * sourced from the cartography knowledge level, a separate, independent
     * pool from buildingApDiscountPercent() above.
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

    /**
     * Additive percent bonus applied to the price a player pays across all 3
     * trade channels (BarService, MerchantService, CorporateContactService),
     * sourced from the trade knowledge level — additive to the existing
     * TradingPostService per-channel discount, not a replacement.
     */
    public function tradePriceBonusPercent(int $colonyId): int
    {
        $level = (int) DB::table('colony_researches')
            ->where('colony_id', $colonyId)
            ->where('research_id', (int) config('knowledge.trade.id'))
            ->value('level');

        $curve = config('knowledge.trade.trade_price_bonus_per_lv', []);

        return GameTick::cumulativeCurveYield($curve, $level);
    }

    /** Pure discount math, factored out so the floor logic is testable without DB state. */
    public static function applyDiscount(int $base, int $discountPercent, float $minCostFactor): int
    {
        $floor = (int) ceil($base * $minCostFactor);
        $discounted = (int) round($base * (1 - $discountPercent / 100));

        return max($floor, $discounted);
    }
}
