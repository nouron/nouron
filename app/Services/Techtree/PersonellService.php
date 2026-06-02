<?php

namespace App\Services\Techtree;

use App\Models\Advisor;
use App\Services\Concerns\ValidatesId;
use App\Services\MoralService;
use App\Services\ResourcesService;
use App\Services\TickService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * PersonellService — manages advisors and action points.
 *
 * Advisors are individual entities stored in the `advisors` table.
 * Each advisor has a rank (1–3) that determines AP per tick:
 *   Junior (1) = 4 AP, Senior (2) = 7 AP, Experte (3) = 12 AP
 *
 * AP types and their scopes (all colony-scoped):
 *   construction  — advisors.engineer   colony-scoped
 *   research      — advisors.scientist  colony-scoped
 *   economy       — advisors.trader     colony-scoped
 *   strategy      — advisors.stratege   colony-scoped
 *   navigation    — advisors.pilot      colony-scoped
 *
 * Advisor IDs come exclusively from config/advisors.php — never hardcode them.
 * Use PersonellService::idFor('engineer') etc. for all lookups.
 */
class PersonellService
{
    use ValidatesId;

    const DEFAULT_ACTIONPOINTS = 4;    // Junior AP fallback

    public static function idFor(string $key): int
    {
        return (int) config("advisors.{$key}.id");
    }

    public static function allIds(): array
    {
        return collect(config('advisors'))->pluck('id')->all();
    }

    public function __construct(
        private readonly TickService      $tickService,
        private readonly MoralService     $moralService,
        private readonly ResourcesService $resourcesService,
    ) {}

    // ── AP calculation ────────────────────────────────────────────────────────

    public function getTotalActionPoints(string $type, int $scopeId): int
    {
        [$personellId, $scope] = $this->resolveType($type);
        if (!$personellId) {
            return 0;
        }

        $baseAp = Advisor::where('personell_id', $personellId)
            ->whereNull('unavailable_until_tick')
            ->where('colony_id', $scopeId)
            ->get()
            ->sum(fn(Advisor $a) => $a->getApPerTick());

        // Apply moral AP multiplier for colony-scoped types.
        $moral      = $this->moralService->getMoral($scopeId);
        $multiplier = $this->moralService->getApMultiplier($moral);
        return (int) round($baseAp * $multiplier);
    }

    public function getAvailableActionPoints(string $type, int $scopeId): int
    {
        [$personellId, $scope] = $this->resolveType($type);
        if (!$personellId) {
            return 0;
        }

        $total = $this->getTotalActionPoints($type, $scopeId);
        $tick  = $this->tickService->getTickCount();

        $locked = DB::table('locked_actionpoints')
            ->where('tick', $tick)
            ->where('scope_type', $scope)
            ->where('scope_id', $scopeId)
            ->where('personell_id', $personellId)
            ->value('spend_ap') ?? 0;

        return max(0, $total - (int) $locked);
    }

    public function lockActionPoints(string $type, int $scopeId, int $ap): bool
    {
        [$personellId, $scope] = $this->resolveType($type);
        if (!$personellId) {
            return false;
        }

        $tick     = $this->tickService->getTickCount();
        $existing = DB::table('locked_actionpoints')
            ->where(['tick' => $tick, 'scope_type' => $scope, 'scope_id' => $scopeId, 'personell_id' => $personellId])
            ->value('spend_ap') ?? 0;

        DB::table('locked_actionpoints')->updateOrInsert(
            ['tick' => $tick, 'scope_type' => $scope, 'scope_id' => $scopeId, 'personell_id' => $personellId],
            ['spend_ap' => $existing + abs($ap)]
        );

        return true;
    }

    // ── Convenience wrappers ──────────────────────────────────────────────────

    public function getConstructionPoints(int $colonyId): int
    {
        return $this->getAvailableActionPoints('construction', $colonyId);
    }

    public function getResearchPoints(int $colonyId): int
    {
        return $this->getAvailableActionPoints('research', $colonyId);
    }

    public function getEconomyPoints(int $colonyId): int
    {
        return $this->getAvailableActionPoints('economy', $colonyId);
    }

    public function getStrategyPoints(int $colonyId): int
    {
        return $this->getAvailableActionPoints('strategy', $colonyId);
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
            $ccLevel  = (int) (DB::table('colony_buildings')
                ->where('colony_id', $colonyId)
                ->where('building_id', config('buildings.commandCenter.id'))
                ->value('level') ?? 0);
            $maxSlots = min($ccLevel, (int) config('game.advisor.max_slots', 5));
            $usedSlots = Advisor::where('colony_id', $colonyId)->count();
            if ($usedSlots >= $maxSlots) {
                return 'slot_full';
            }

            // Credits check and deduction.
            if (!config('game.bypass.resource_costs')) {
                $advisorCfg  = collect(config('advisors'))->firstWhere('id', $personellId);
                $creditsCost = (int) ($advisorCfg['credits'] ?? 0);
                if ($creditsCost > 0) {
                    $canAfford = $this->resourcesService->check(
                        [['resource_id' => ResourcesService::RES_CREDITS, 'amount' => $creditsCost]],
                        $colonyId
                    );
                    if (!$canAfford) {
                        return 'insufficient_credits';
                    }
                    $this->resourcesService->decreaseAmount($colonyId, ResourcesService::RES_CREDITS, $creditsCost);
                }
            }

            return Advisor::create([
                'user_id'      => $userId,
                'personell_id' => $personellId,
                'colony_id'    => $colonyId,
                'rank'         => max(1, min(3, $rank)),
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
        $ccLevel  = (int) (DB::table('colony_buildings')
            ->where('colony_id', $colonyId)
            ->where('building_id', config('buildings.commandCenter.id'))
            ->value('level') ?? 0);
        $maxSlots  = min($ccLevel, (int) config('game.advisor.max_slots', 5));
        $usedSlots = Advisor::where('colony_id', $colonyId)->count();

        return [
            'cc_level' => $ccLevel,
            'max'      => $maxSlots,
            'used'     => $usedSlots,
            'free'     => max(0, $maxSlots - $usedSlots),
        ];
    }

    /**
     * Fire an advisor — sets them unemployed (colony_id = null).
     * The advisor record is NOT deleted and remains available for re-hire or trade.
     */
    public function fire(int $advisorId): bool
    {
        return (bool) Advisor::where('id', $advisorId)->update([
            'colony_id'              => null,
            'unavailable_until_tick' => $this->tickService->getTickCount(),
        ]);
    }

    // ── Commander assignment ──────────────────────────────────────────────────

    /**
     * Assign the colony's navigation advisor as commander of the given fleet.
     *
     * Throws RuntimeException if:
     *   - no navigation advisor exists on the colony
     *   - the advisor is currently unavailable (cooldown tick still active)
     *   - the fleet already has a commander assigned
     */
    public function assignCommander(int $colonyId, int $fleetId, int $userId): bool
    {
        $navigationId = self::idFor('pilot');

        $advisor = Advisor::where('colony_id', $colonyId)
            ->where('personell_id', $navigationId)
            ->first();

        if (!$advisor) {
            throw new \RuntimeException(__('fleet.commander_no_navigator'));
        }

        $currentTick = $this->tickService->getTickCount();
        if (!$advisor->isAvailable($currentTick)) {
            throw new \RuntimeException(__('fleet.commander_navigator_unavailable'));
        }

        $alreadyHasCommander = Advisor::where('fleet_id', $fleetId)
            ->where('is_commander', 1)
            ->exists();

        if ($alreadyHasCommander) {
            throw new \RuntimeException(__('fleet.commander_already_assigned'));
        }

        DB::transaction(function () use ($advisor, $fleetId) {
            $advisor->colony_id    = null;
            $advisor->fleet_id     = $fleetId;
            $advisor->is_commander = 1;
            $advisor->save();
        });

        return true;
    }

    /**
     * Remove the commander from the given fleet and return them to the colony.
     * Returns false if no commander is assigned (idempotent).
     */
    public function removeCommander(int $fleetId, int $colonyId, int $userId): bool
    {
        $advisor = Advisor::where('fleet_id', $fleetId)
            ->where('is_commander', 1)
            ->first();

        if (!$advisor) {
            return false;
        }

        DB::transaction(function () use ($advisor, $colonyId) {
            $advisor->colony_id    = $colonyId;
            $advisor->fleet_id     = null;
            $advisor->is_commander = 0;
            $advisor->save();
        });

        return true;
    }

    // ── AP credit (merchant / external grants) ───────────────────────────────

    /**
     * Credit AP directly to a colony, bypassing the normal per-tick earn cycle.
     *
     * This is used by the Traveling Merchant when the player buys an AP item.
     * The grant is recorded as "negative spend" on the current tick so that
     * getAvailableActionPoints() returns a higher value until the AP is consumed.
     *
     * ap_flex   → type is 'any': distribute the amount across all advisor types
     *             that have at least one active advisor on the colony, spreading
     *             it as evenly as possible (remainder goes to the first type).
     *             If no advisors are present, falls back to 'construction'.
     *
     * ap_targeted → type is a specific AP type key (e.g. 'research'):
     *             credit the full amount to that single pool only.
     *
     * @param  int    $colonyId
     * @param  string $apType   'any' for flex, or a specific type key
     * @param  int    $amount   total AP to grant (must be > 0)
     */
    public function creditAp(int $colonyId, string $apType, int $amount): void
    {
        if ($amount <= 0) {
            return;
        }

        $tick = $this->tickService->getTickCount();

        if ($apType === 'any') {
            $this->creditApFlex($colonyId, $amount, $tick);
        } else {
            $this->creditApToType($colonyId, $apType, $amount, $tick);
        }
    }

    /**
     * Distribute AP evenly across all advisor types with active advisors on the colony.
     * Falls back to 'construction' when no advisors are present.
     */
    private function creditApFlex(int $colonyId, int $amount, int $tick): void
    {
        // Collect all AP types that have at least one advisor currently on the colony.
        $allTypes      = ['construction', 'research', 'economy', 'strategy', 'navigation'];
        $activeTypes   = [];

        foreach ($allTypes as $type) {
            [$personellId] = $this->resolveType($type);
            if (!$personellId) {
                continue;
            }
            $hasAdvisor = Advisor::where('colony_id', $colonyId)
                ->where('personell_id', $personellId)
                ->whereNull('unavailable_until_tick')
                ->exists();
            if ($hasAdvisor) {
                $activeTypes[] = $type;
            }
        }

        // If no advisors are active, grant to construction as a sensible default.
        if (empty($activeTypes)) {
            $activeTypes = ['construction'];
        }

        $count     = count($activeTypes);
        $base      = intdiv($amount, $count);
        $remainder = $amount % $count;

        foreach ($activeTypes as $i => $type) {
            $grant = $base + ($i === 0 ? $remainder : 0);
            if ($grant > 0) {
                $this->creditApToType($colonyId, $type, $grant, $tick);
            }
        }
    }

    /**
     * Grant AP to a single named type by recording a negative spend_ap entry
     * for the current tick. This increases the available AP budget by $amount.
     */
    private function creditApToType(int $colonyId, string $apType, int $amount, int $tick): void
    {
        [$personellId, $scope] = $this->resolveType($apType);

        // Unknown type — silently skip rather than crash so a bad payload can't break a purchase.
        if (!$personellId) {
            return;
        }

        $existing = (int) (DB::table('locked_actionpoints')
            ->where([
                'tick'         => $tick,
                'scope_type'   => $scope,
                'scope_id'     => $colonyId,
                'personell_id' => $personellId,
            ])
            ->value('spend_ap') ?? 0);

        // A negative spend_ap increases the available AP pool for this tick.
        $newValue = $existing - $amount;

        DB::table('locked_actionpoints')->updateOrInsert(
            [
                'tick'         => $tick,
                'scope_type'   => $scope,
                'scope_id'     => $colonyId,
                'personell_id' => $personellId,
            ],
            ['spend_ap' => $newValue]
        );
    }

    // ── Queries ───────────────────────────────────────────────────────────────

    public function getColonyAdvisors(int $colonyId): Collection
    {
        return Advisor::where('colony_id', $colonyId)->get();
    }

    // ── Internal ──────────────────────────────────────────────────────────────

    /**
     * Returns [personell_id, scope_type] for the given AP type string.
     * All types are colony-scoped.
     */
    private function resolveType(string $type): array
    {
        return match (strtolower($type)) {
            'construction' => [self::idFor('engineer'),  'colony'],
            'research'     => [self::idFor('scientist'), 'colony'],
            'economy'      => [self::idFor('trader'),    'colony'],
            'strategy'     => [self::idFor('strategist'), 'colony'],
            'navigation'   => [self::idFor('pilot'),     'colony'],
            default        => [null, null],
        };
    }
}
