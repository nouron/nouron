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
 * AP types and their scopes:
 *   construction  — Ingenieur (35),       colony-scoped
 *   research      — Wissenschaftler (36), colony-scoped
 *   navigation    — Kommandant (89),      fleet-scoped (is_commander=true)
 *   economy       — Händler (92),         colony-scoped
 */
class PersonellService
{
    use ValidatesId;

    const PERSONELL_ID_ENGINEER  = 35;
    const PERSONELL_ID_SCIENTIST = 36;
    const PERSONELL_ID_PILOT     = 89;  // Kommandant
    const PERSONELL_ID_TRADER    = 92;

    const DEFAULT_ACTIONPOINTS = 4;    // Junior AP fallback

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

        $query = Advisor::where('personell_id', $personellId)
            ->whereNull('unavailable_until_tick');

        if ($scope === 'fleet') {
            $query->where('fleet_id', $scopeId)->where('is_commander', true);
        } else {
            $query->where('colony_id', $scopeId);
        }

        $baseAp = $query->get()->sum(fn(Advisor $a) => $a->getApPerTick());

        // Apply moral AP multiplier for colony-scoped types.
        if ($scope === 'colony') {
            $moral      = $this->moralService->getMoral($scopeId);
            $multiplier = $this->moralService->getApMultiplier($moral);
            return (int) round($baseAp * $multiplier);
        }

        return $baseAp;
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

    public function getFleetNavigationPoints(int $fleetId): int
    {
        return $this->getAvailableActionPoints('navigation', $fleetId);
    }

    public function getEconomyPoints(int $colonyId): int
    {
        return $this->getAvailableActionPoints('economy', $colonyId);
    }

    // ── Hire / Fire / Assign ─────────────────────────────────────────────────

    /**
     * Hire a new advisor and assign them to a colony.
     */
    public function hire(int $userId, int $personellId, int $colonyId, int $rank = 1): Advisor|false
    {
        $this->validateId($userId);
        $this->validateId($colonyId);

        if (!config('game.dev_mode')) {
            $cost = (int) config('game.supply.cost_advisor', 2);
            if ($this->resourcesService->getFreeSupply($colonyId) < $cost) {
                return false;
            }
        }

        return Advisor::create([
            'user_id'      => $userId,
            'personell_id' => $personellId,
            'colony_id'    => $colonyId,
            'fleet_id'     => null,
            'is_commander' => false,
            'rank'         => max(1, min(3, $rank)),
            'active_ticks' => 0,
        ]);
    }

    /**
     * Fire an advisor — sets them unemployed (colony_id/fleet_id = null).
     * The advisor record is NOT deleted and remains available for re-hire or trade.
     */
    public function fire(int $advisorId): bool
    {
        return (bool) Advisor::where('id', $advisorId)->update([
            'colony_id'    => null,
            'fleet_id'     => null,
            'is_commander' => false,
        ]);
    }

    /**
     * Assign a Kommandant to command a fleet.
     * Only advisors with personell.can_command_fleet=true may be assigned.
     *
     * @throws \RuntimeException if the advisor type cannot command a fleet
     */
    public function assignToFleet(int $advisorId, int $fleetId): bool
    {
        $advisor = Advisor::find($advisorId);
        if (!$advisor) {
            return false;
        }

        $canCommand = DB::table('personell')
            ->where('id', $advisor->personell_id)
            ->value('can_command_fleet');

        if (!$canCommand) {
            throw new \RuntimeException('Nur Kommandanten können Flotten führen.');
        }

        $advisor->update([
            'colony_id'    => null,
            'fleet_id'     => $fleetId,
            'is_commander' => true,
        ]);

        return true;
    }

    /**
     * Unassign a Kommandant from a fleet and return them to a colony.
     */
    public function unassignFromFleet(int $advisorId, int $colonyId): bool
    {
        return (bool) Advisor::where('id', $advisorId)->update([
            'fleet_id'     => null,
            'colony_id'    => $colonyId,
            'is_commander' => false,
        ]);
    }

    // ── Queries ───────────────────────────────────────────────────────────────

    public function getColonyAdvisors(int $colonyId): Collection
    {
        return Advisor::where('colony_id', $colonyId)->get();
    }

    public function getFleetCommander(int $fleetId): ?Advisor
    {
        return Advisor::where('fleet_id', $fleetId)
            ->where('is_commander', true)
            ->first();
    }

    // ── Internal ──────────────────────────────────────────────────────────────

    /**
     * Returns [personell_id, scope_type] for the given AP type string.
     */
    private function resolveType(string $type): array
    {
        return match (strtolower($type)) {
            'construction' => [self::PERSONELL_ID_ENGINEER,  'colony'],
            'research'     => [self::PERSONELL_ID_SCIENTIST, 'colony'],
            'navigation'   => [self::PERSONELL_ID_PILOT,     'fleet'],
            'economy'      => [self::PERSONELL_ID_TRADER,    'colony'],
            default        => [null, null],
        };
    }
}
