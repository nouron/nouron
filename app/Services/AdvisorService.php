<?php

namespace App\Services;

use App\Enums\BuildingId;
use App\Models\Advisor;
use App\Services\Concerns\ValidatesId;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * AdvisorService — manages advisors and the colony's shared AP pool.
 *
 * Advisors are individual entities stored in the `advisors` table.
 * Each advisor has a rank (1–3) that adds a flat AP contribution to the
 * colony's single shared pool (GDD §13.1), regardless of advisor type:
 *   Junior (1) = +2 AP, Senior (2) = +3 AP, Experte (3) = +4 AP
 *
 * Advisor IDs come exclusively from config/advisors.php — never hardcode them.
 * Use AdvisorService::idFor('engineer') etc. for all lookups.
 */
class AdvisorService
{
    use ValidatesId;

    const DEFAULT_ACTIONPOINTS = 2;    // Junior AP fallback

    public static function idFor(string $key): int
    {
        return (int) config("advisors.{$key}.id");
    }

    public static function allIds(): array
    {
        return collect(config('advisors'))->pluck('id')->all();
    }

    public function __construct(
        private readonly TickService $tickService,
        private readonly TrustService $trustService,
        private readonly ResourcesService $resourcesService,
    ) {}

    // ── AP calculation ────────────────────────────────────────────────────────
    // One shared pool per colony (GDD §13.1) — no domain parameter anymore.
    // Domains (construction/research/navigation/economy) will only determine
    // an efficiency bonus in the future (§13.3, separate implementation step),
    // not which pool gets filled.

    public function getTotalActionPoints(int $colonyId): int
    {
        return $this->getApBreakdown($colonyId)['total'];
    }

    /**
     * Breakdown of a colony's total AP — base/advisor/multiplier components,
     * for display in the resource-bar AP chip popup.
     *
     * @return array{base: int, advisor: int, multiplier: float, plague_multiplier: float, total: int}
     */
    public function getApBreakdown(int $colonyId): array
    {
        $baseAp = (int) config('game.ap.base', 12);
        $advisorAp = Advisor::where('colony_id', $colonyId)
            ->whereNull('unavailable_until_tick')
            ->get()
            ->sum(fn (Advisor $a) => $a->getApPerTick());

        $trust = $this->trustService->getTrust($colonyId);
        $multiplier = $this->trustService->getApMultiplier($trust);

        // Seuchenausbruch (GDD §9): temporary AP-reduction debuff while
        // glx_colonies.plague_until_tick is still in the future.
        $plagueUntilTick = DB::table('glx_colonies')->where('id', $colonyId)->value('plague_until_tick');
        $plagueActive = $plagueUntilTick !== null && (int) $plagueUntilTick >= $this->tickService->getTickCount();
        $plagueMultiplier = $plagueActive ? (1 - (float) config('game.encounter.plague.ap_reduction_pct', 0.20)) : 1.0;

        return [
            'base' => $baseAp,
            'advisor' => (int) $advisorAp,
            'multiplier' => $multiplier,
            'plague_multiplier' => $plagueMultiplier,
            'total' => (int) round(($baseAp + $advisorAp) * $multiplier * $plagueMultiplier),
        ];
    }

    public function getAvailableActionPoints(int $colonyId): int
    {
        $total = $this->getTotalActionPoints($colonyId);
        $tick = $this->tickService->getTickCount();

        $locked = (int) (DB::table('locked_actionpoints')
            ->where('tick', $tick)
            ->where('scope_type', 'colony')
            ->where('scope_id', $colonyId)
            ->sum('spend_ap'));

        return max(0, $total - $locked);
    }

    /**
     * Locks AP against the shared colony pool for the current tick.
     * $personellId identifies which advisor/action triggered the lock, purely
     * for audit purposes (locked_actionpoints.personell_id) — it no longer
     * partitions the pool. Falls back to the engineer's id when the caller
     * doesn't know/care which advisor to attribute it to.
     */
    public function lockActionPoints(int $colonyId, int $ap, ?int $personellId = null): bool
    {
        $personellId ??= self::idFor('engineer');
        $tick = $this->tickService->getTickCount();

        $existing = DB::table('locked_actionpoints')
            ->where(['tick' => $tick, 'scope_type' => 'colony', 'scope_id' => $colonyId, 'personell_id' => $personellId])
            ->value('spend_ap') ?? 0;

        DB::table('locked_actionpoints')->updateOrInsert(
            ['tick' => $tick, 'scope_type' => 'colony', 'scope_id' => $colonyId, 'personell_id' => $personellId],
            ['spend_ap' => $existing + abs($ap)]
        );

        return true;
    }

    // ── Hire / Fire ───────────────────────────────────────────────────────────

    /**
     * Hire a new advisor and assign them to a colony.
     *
     * Returns the created Advisor on success, or one of these error strings:
     *   'duplicate'            — an advisor of this type already exists on the colony
     *   'slot_full'            — no free advisor slot (CC level too low)
     *   'insufficient_credits' — not enough credits to pay the hire cost
     */
    public function hire(int $userId, int $personellId, int $colonyId, int $rank = 1): Advisor|string
    {
        $this->validateId($userId);
        $this->validateId($colonyId);

        return DB::transaction(function () use ($userId, $personellId, $colonyId, $rank) {
            // Duplicate check — slot system allows exactly 1 advisor per type per colony.
            if (Advisor::where('colony_id', $colonyId)->where('personell_id', $personellId)->exists()) {
                return 'duplicate';
            }

            // Same-tick re-hire guard — prevent fire→hire→fire exploit within one tick.
            $currentTick = $this->tickService->getTickCount();
            if (Advisor::where('user_id', $userId)
                ->where('personell_id', $personellId)
                ->whereNull('colony_id')
                ->where('unavailable_until_tick', $currentTick)
                ->exists()) {
                return 'dismissed_this_tick';
            }

            // CC-Level gate — slots available = min(cc_level, max_slots).
            $ccLevel = (int) (DB::table('colony_buildings')
                ->where('colony_id', $colonyId)
                ->where('building_id', BuildingId::CommandCenter->value)
                ->value('level') ?? 0);
            $maxSlots = min($ccLevel, (int) config('game.advisor.max_slots', 4));
            $usedSlots = Advisor::where('colony_id', $colonyId)->count();
            if ($usedSlots >= $maxSlots) {
                return 'slot_full';
            }

            // Path-building gate — scientist/pilot/trader require the matching building placed.
            $pathBuildingMap = [
                self::idFor('scientist') => 31,  // sciencelab
                self::idFor('pilot') => 44,       // hangar
                self::idFor('trader') => 52,      // bar
            ];
            if (isset($pathBuildingMap[$personellId])) {
                $requiredBuildingId = $pathBuildingMap[$personellId];
                $isPlaced = DB::table('colony_buildings')
                    ->where('colony_id', $colonyId)
                    ->where('building_id', $requiredBuildingId)
                    ->where('level', '>', 0)
                    ->whereNotNull('tile_x')
                    ->exists();
                if (! $isPlaced) {
                    return 'path_building_missing';
                }
            }

            // Credits check and deduction.
            if (! config('game.bypass.resource_costs')) {
                $advisorCfg = collect(config('advisors'))->firstWhere('id', $personellId);
                $creditsCost = (int) ($advisorCfg['credits'] ?? 0);
                if ($creditsCost > 0) {
                    $canAfford = $this->resourcesService->check(
                        [['resource_id' => ResourcesService::RES_CREDITS, 'amount' => $creditsCost]],
                        $colonyId
                    );
                    if (! $canAfford) {
                        return 'insufficient_credits';
                    }
                    $this->resourcesService->decreaseAmount($colonyId, ResourcesService::RES_CREDITS, $creditsCost);
                }
            }

            return Advisor::create([
                'user_id' => $userId,
                'personell_id' => $personellId,
                'colony_id' => $colonyId,
                'rank' => max(1, min(3, $rank)),
                'active_ticks' => 0,
            ]);
        });
    }

    /**
     * Returns slot usage info for a colony's advisor panel.
     *
     * @return array{max: int, used: int, free: int, cc_level: int}
     */
    public function getAdvisorSlotInfo(int $colonyId): array
    {
        $ccLevel = (int) (DB::table('colony_buildings')
            ->where('colony_id', $colonyId)
            ->where('building_id', BuildingId::CommandCenter->value)
            ->value('level') ?? 0);
        $maxSlots = min($ccLevel, (int) config('game.advisor.max_slots', 4));
        $usedSlots = Advisor::where('colony_id', $colonyId)->count();

        return [
            'cc_level' => $ccLevel,
            'max' => $maxSlots,
            'used' => $usedSlots,
            'free' => max(0, $maxSlots - $usedSlots),
        ];
    }

    /**
     * Fire an advisor — sets them unemployed (colony_id = null).
     * The advisor record is NOT deleted and remains available for re-hire or trade.
     */
    public function fire(int $advisorId): bool
    {
        return (bool) Advisor::where('id', $advisorId)->update([
            'colony_id' => null,
            'unavailable_until_tick' => $this->tickService->getTickCount(),
        ]);
    }

    // ── AP credit (merchant / external grants) ───────────────────────────────

    /**
     * Credit AP directly to a colony's shared pool, bypassing the normal
     * per-tick earn cycle. Used by the Traveling Merchant when the player
     * buys an AP item (ap_flex / ap_targeted, both credit the same single
     * pool now — the item's flavor text still varies, the effect doesn't).
     *
     * Recorded as "negative spend" on the current tick so that
     * getAvailableActionPoints() returns a higher value until consumed.
     */
    public function creditAp(int $colonyId, int $amount): void
    {
        if ($amount <= 0) {
            return;
        }

        $tick = $this->tickService->getTickCount();
        $personellId = self::idFor('engineer');

        $existing = (int) (DB::table('locked_actionpoints')
            ->where(['tick' => $tick, 'scope_type' => 'colony', 'scope_id' => $colonyId, 'personell_id' => $personellId])
            ->value('spend_ap') ?? 0);

        DB::table('locked_actionpoints')->updateOrInsert(
            ['tick' => $tick, 'scope_type' => 'colony', 'scope_id' => $colonyId, 'personell_id' => $personellId],
            ['spend_ap' => $existing - $amount]
        );
    }

    // ── Queries ───────────────────────────────────────────────────────────────

    public function getColonyAdvisors(int $colonyId): Collection
    {
        return Advisor::where('colony_id', $colonyId)->get();
    }

    // ── Internal ──────────────────────────────────────────────────────────────

    /**
     * Returns the personell_id for a given advisor-type key — used by hire()
     * to resolve which advisor the player is hiring. No longer used for AP
     * pool calculation (there is only one pool, see getTotalActionPoints()).
     */
    public function resolveType(string $type): ?int
    {
        return match (strtolower($type)) {
            'construction' => self::idFor('engineer'),
            'research' => self::idFor('scientist'),
            'economy' => self::idFor('trader'),
            'navigation' => self::idFor('pilot'),
            default => null,
        };
    }
}
