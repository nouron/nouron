<?php

namespace App\Services;

use App\Console\Commands\GameTick;
use App\Enums\BuildingId;
use Illuminate\Support\Facades\DB;

/**
 * Hosts several independent AP-cost discount and price-bonus pools (GDD §13.3),
 * each sourced from its own knowledge/building level and combined additively
 * within its own pool only — no cross-pool stacking. AP discounts are absolute AP
 * (AP scale 2026-11-13 §4/§5.1): permanent ones only touch costs of at least
 * game.min_discountable_cost_ap, and nothing drops below its absolute floor.
 *   - building-project AP discount, sourced from the construction knowledge
 *     curve (buildingApDiscount()), plus one-off Cantina vouchers;
 *   - knowledge-levelup AP discount, sourced from the Analytik-Labor
 *     (sciencelab) building level (knowledgeApDiscount());
 *   - hangar mission AP (missionApBase() + cartography, effectiveMissionApCost());
 *   - trade-price bonus across all 3 trade channels, sourced from the trade
 *     knowledge level (tradePriceBonusPercent());
 *   - Cantina-Verhandlung success-chance bonus, sourced from the trade
 *     knowledge level (tradeNegotiateChanceBonusPercent()).
 */
class ProjectBonusService
{
    /** Knowledge keys from config/knowledge.php that discount building projects (trade is thematically not one — A13). */
    private const DOMAIN_KNOWLEDGE_KEYS = ['construction'];

    public function __construct(private readonly TickService $tickService) {}

    /** Absolute AP the construction knowledge takes off a (discountable) building levelup. */
    public function buildingApDiscount(int $colonyId): int
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
        $discount = self::isDiscountable($baseApForLevelup) ? $this->buildingApDiscount($colonyId) : 0;
        $discount += $this->activeBuildingDiscountVoucherAp($colonyId);

        return self::applyDiscount($baseApForLevelup, $discount, self::projectFloor());
    }

    /**
     * Absolute AP discount of a Cantina-Anliegen voucher (A41 — Vesper/information_broker
     * or Aldra/founder; column discount_pct holds AP since the AP scale 2026-11-13), folded
     * into effectiveApForLevelup() so every read site (invest action AND the sidebar
     * cost-preview endpoints) sees the same discounted number — see CLAUDE.md
     * discount-wiring checklist. Picks the largest unconsumed, unexpired voucher.
     */
    public function activeBuildingDiscountVoucherAp(int $colonyId): int
    {
        $tick = $this->tickService->getTickCount();

        $voucher = DB::table('colony_building_discount_vouchers')
            ->where('colony_id', $colonyId)
            ->whereNull('consumed_tick')
            ->where(function ($q) use ($tick) {
                $q->whereNull('expires_tick')->orWhere('expires_tick', '>', $tick);
            })
            ->orderByDesc('discount_pct')
            ->first();

        return $voucher !== null ? (int) $voucher->discount_pct : 0;
    }

    /**
     * Consumes the currently active building-discount voucher (if any) — called
     * once a building level-up actually completes while a voucher was active.
     * No-op if none is active.
     */
    public function consumeActiveBuildingDiscountVoucher(int $colonyId): void
    {
        $tick = $this->tickService->getTickCount();

        $voucher = DB::table('colony_building_discount_vouchers')
            ->where('colony_id', $colonyId)
            ->whereNull('consumed_tick')
            ->where(function ($q) use ($tick) {
                $q->whereNull('expires_tick')->orWhere('expires_tick', '>', $tick);
            })
            ->orderByDesc('discount_pct')
            ->first();

        if ($voucher !== null) {
            DB::table('colony_building_discount_vouchers')
                ->where('id', $voucher->id)
                ->update(['consumed_tick' => $tick]);
        }
    }

    /**
     * Absolute AP discount for Kenntnis-Levelups, sourced from the Analytik-Labor
     * (sciencelab) building level (buildings.sciencelab.knowledge_ap_cost_reduction_per_lv,
     * cumulative) — a separate, independent pool from buildingApDiscount() above.
     */
    public function knowledgeApDiscount(int $colonyId): int
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
        $discount = self::isDiscountable($baseApForLevelup) ? $this->knowledgeApDiscount($colonyId) : 0;

        return self::applyDiscount($baseApForLevelup, $discount, self::projectFloor());
    }

    /** Mission dispatch AP before bonuses: missions.ap_by_distance, last entry beyond the table. */
    public static function missionApBase(int $solDistance): int
    {
        $table = (array) config('missions.ap_by_distance', []);
        if ($table === []) {
            return 0;
        }
        ksort($table);

        return (int) ($table[$solDistance] ?? ($solDistance < array_key_first($table) ? 0 : end($table)));
    }

    /**
     * Dispatch AP of a hangar mission: the distance table minus the cartography cut for
     * long missions (knowledge.cartography.mission_ap_reduction), never below
     * game.action_min_cost_ap. Used by the dispatch action and the catalog preview alike.
     */
    public function effectiveMissionApCost(int $colonyId, int $solDistance): int
    {
        $base = self::missionApBase($solDistance);
        $rule = (array) config('knowledge.cartography.mission_ap_reduction', []);
        $discount = 0;
        if ($rule !== [] && $solDistance >= (int) ($rule['min_distance'] ?? PHP_INT_MAX)
            && $this->knowledgeLevel($colonyId, 'cartography') >= (int) ($rule['from_level'] ?? PHP_INT_MAX)) {
            $discount = (int) ($rule['ap'] ?? 0);
        }

        return self::applyDiscount($base, $discount, (int) config('game.action_min_cost_ap', 1));
    }

    /** True when the cartography level grants a free first exploration per Sol. */
    public function hasFreeDailyExploration(int $colonyId): bool
    {
        $from = config('knowledge.cartography.free_explore_from_level');

        return $from !== null && $this->knowledgeLevel($colonyId, 'cartography') >= (int) $from;
    }

    private function knowledgeLevel(int $colonyId, string $knowledgeKey): int
    {
        return (int) DB::table('colony_researches')
            ->where('colony_id', $colonyId)
            ->where('research_id', (int) config("knowledge.{$knowledgeKey}.id"))
            ->value('level');
    }

    /**
     * Additive percent bonus applied to the price a player pays across all 3
     * trade channels (BarService, MerchantService),
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

    /**
     * Percentage points the trade knowledge adds to the Cantina-Verhandlung success
     * chance (GDD §12/§10, A13), cumulative over the researched levels. The caller
     * decides whether it applies at all (only when a Konsul negotiates).
     */
    public function tradeNegotiateChanceBonusPercent(int $colonyId): int
    {
        $level = (int) DB::table('colony_researches')
            ->where('colony_id', $colonyId)
            ->where('research_id', (int) config('knowledge.trade.id'))
            ->value('level');

        $curve = config('knowledge.trade.negotiate_chance_bonus_per_lv', []);

        return GameTick::cumulativeCurveYield($curve, $level);
    }

    /**
     * Pure discount math (absolute AP): base minus discount, never below the floor — and a
     * base already below the floor is never raised.
     */
    public static function applyDiscount(int $base, int $discountAp, int $floor): int
    {
        return max(min($base, $floor), $base - max(0, $discountAp));
    }

    /** Permanent discounts only touch costs of at least game.min_discountable_cost_ap (§5.1.2). */
    public static function isDiscountable(int $baseAp): bool
    {
        return $baseAp >= (int) config('game.min_discountable_cost_ap', 6);
    }

    private static function projectFloor(): int
    {
        return (int) config('game.project_min_cost_ap', 2);
    }
}
