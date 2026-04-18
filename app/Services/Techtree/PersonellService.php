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
            'colony_id' => null,
        ]);
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
            'strategy'     => [self::idFor('stratege'),  'colony'],
            'navigation'   => [self::idFor('pilot'),     'colony'],
            default        => [null, null],
        };
    }
}
