<?php

namespace App\Services\Techtree;

use App\Services\Concerns\ValidatesId;
use App\Services\ResourcesService;
use App\Services\TickService;
use Illuminate\Support\Facades\DB;

/**
 * PersonellService — manages colony personell (engineers, scientists, pilots, traders).
 *
 * Each personell type grants a specific pool of Action Points (AP) per tick:
 *
 *   Engineer  (id=35, industry) → construction AP
 *       Used for: building levelup/leveldown, ship construction
 *
 *   Scientist (id=36, civil)    → research AP
 *       Used for: research levelup/leveldown
 *
 *   Pilot/Commander (id=89, military) → navigation AP
 *       Scoped to the FLEET (not the colony). The commander flies with the fleet
 *       and is stored in fleet_personell. Navigation-AP are therefore fleet-scoped.
 *
 *   Trader    (id=92, economy)  → economy AP
 *       Used for: creating/maintaining trade route offers
 *       Scoped to the colony that owns the trade center.
 *
 * Formula: totalAP = level * DEFAULT_ACTIONPOINTS + DEFAULT_ACTIONPOINTS
 * Available AP = totalAP − locked AP for the current tick.
 *
 * AP are locked per (tick, scope_type, scope_id, personell_id):
 *   - scope_type='colony' for construction / research / economy
 *   - scope_type='fleet'  for navigation
 *
 * Personell cannot receive AP investments (invest() always returns false).
 * Use hire()/fire() which delegate to levelup()/leveldown().
 */
class PersonellService extends AbstractTechnologyService
{
    const PERSONELL_ID_ENGINEER  = 35;
    const PERSONELL_ID_SCIENTIST = 36;
    const PERSONELL_ID_PILOT     = 89;
    const PERSONELL_ID_TRADER    = 92;
    const DEFAULT_ACTIONPOINTS   = 5;

    protected function masterTable(): string  { return 'personell'; }
    protected function colonyTable(): string  { return 'colony_personell'; }
    protected function costsTable(): string   { return 'personell_costs'; }
    protected function entityIdKey(): string  { return 'personell_id'; }

    /**
     * AP investment is not applicable to personell.
     */
    public function invest(int $colonyId, int $entityId, string $action = 'add', int $points = 1): bool
    {
        return false;
    }

    /**
     * Return the total Navigation-AP for a fleet based on the commander level.
     *
     * Reads fleet_personell WHERE fleet_id = $fleetId AND personell_id = PILOT.
     * Formula: count * DEFAULT_ACTIONPOINTS + DEFAULT_ACTIONPOINTS
     * When no commander is assigned the minimum (DEFAULT_ACTIONPOINTS) is returned.
     */
    public function getFleetNavigationPoints(int $fleetId): int
    {
        $this->validateId($fleetId);

        $row = DB::table('fleet_personell')
            ->where('fleet_id', $fleetId)
            ->where('personell_id', self::PERSONELL_ID_PILOT)
            ->first();

        $count = $row ? (int) $row->count : 0;

        return $count * self::DEFAULT_ACTIONPOINTS + self::DEFAULT_ACTIONPOINTS;
    }

    /**
     * Return the total AP for a given type based on the personell level.
     *
     * For 'navigation': $scopeId is treated as a fleet_id and reads fleet_personell.
     * For all other types: $scopeId is treated as a colony_id and reads colony_personell.
     *
     * Formula: level * DEFAULT_ACTIONPOINTS + DEFAULT_ACTIONPOINTS
     */
    public function getTotalActionPoints(string $type, int $scopeId): int
    {
        if (strtolower($type) === 'navigation') {
            return $this->getFleetNavigationPoints($scopeId);
        }

        $entityId = match (strtolower($type)) {
            'construction' => self::PERSONELL_ID_ENGINEER,
            'research'     => self::PERSONELL_ID_SCIENTIST,
            'economy'      => self::PERSONELL_ID_TRADER,
            default        => null,
        };

        if (!$entityId) {
            return 0;
        }

        $personell = $this->getColonyEntity($scopeId, $entityId);
        $level     = $personell ? (int) $personell->level : 0;

        return $level * self::DEFAULT_ACTIONPOINTS + self::DEFAULT_ACTIONPOINTS;
    }

    /**
     * Return available construction AP for a colony (engineers).
     */
    public function getConstructionPoints(int $colonyId): int
    {
        return $this->getAvailableActionPoints('construction', $colonyId);
    }

    /**
     * Return available research AP for a colony (scientists).
     */
    public function getResearchPoints(int $colonyId): int
    {
        return $this->getAvailableActionPoints('research', $colonyId);
    }

    /**
     * Return available navigation AP for a fleet (commander / pilot).
     *
     * Unlike the colony-scoped AP methods, $fleetId is a fleet_id here.
     */
    public function getFleetNavPoints(int $fleetId): int
    {
        return $this->getAvailableActionPoints('navigation', $fleetId);
    }

    /**
     * Return available economy AP for a colony (traders).
     */
    public function getEconomyPoints(int $colonyId): int
    {
        return $this->getAvailableActionPoints('economy', $colonyId);
    }

    /**
     * Return available AP (total − locked in current tick) for a given type.
     *
     * For 'navigation': $scopeId is a fleet_id, locked_actionpoints uses scope_type='fleet'.
     * For all other types: $scopeId is a colony_id, locked_actionpoints uses scope_type='colony'.
     */
    public function getAvailableActionPoints(string $type, int $scopeId): int
    {
        $this->validateId($scopeId);

        $entityId = match (strtolower($type)) {
            'construction' => self::PERSONELL_ID_ENGINEER,
            'research'     => self::PERSONELL_ID_SCIENTIST,
            'navigation'   => self::PERSONELL_ID_PILOT,
            'economy'      => self::PERSONELL_ID_TRADER,
            default        => null,
        };

        if (!$entityId) {
            return 0;
        }

        $totalAP     = $this->getTotalActionPoints($type, $scopeId);
        $currentTick = $this->tickService->getTickCount();
        $scopeType   = (strtolower($type) === 'navigation') ? 'fleet' : 'colony';

        $locked = DB::table('locked_actionpoints')
            ->where('tick', $currentTick)
            ->where('scope_type', $scopeType)
            ->where('scope_id', $scopeId)
            ->where('personell_id', $entityId)
            ->value('spend_ap') ?? 0;

        return $totalAP - (int) $locked;
    }

    /**
     * Lock (spend) AP for the current tick.
     *
     * For 'navigation': scope_type='fleet', $scopeId is a fleet_id.
     * For all other types: scope_type='colony', $scopeId is a colony_id.
     *
     * Adds to any existing locked amount for the tick.
     */
    public function lockActionPoints(string $type, int $scopeId, int $ap): bool
    {
        $this->validateId($scopeId);

        $tick     = $this->tickService->getTickCount();
        $entityId = match (strtolower($type)) {
            'construction' => self::PERSONELL_ID_ENGINEER,
            'research'     => self::PERSONELL_ID_SCIENTIST,
            'navigation'   => self::PERSONELL_ID_PILOT,
            'economy'      => self::PERSONELL_ID_TRADER,
            default        => null,
        };

        if (!$entityId) {
            return false;
        }

        $scopeType = (strtolower($type) === 'navigation') ? 'fleet' : 'colony';

        $existing = DB::table('locked_actionpoints')
            ->where([
                'tick'         => $tick,
                'scope_type'   => $scopeType,
                'scope_id'     => $scopeId,
                'personell_id' => $entityId,
            ])
            ->first();

        $currentSpend = $existing ? (int) $existing->spend_ap : 0;

        DB::table('locked_actionpoints')->updateOrInsert(
            [
                'tick'         => $tick,
                'scope_type'   => $scopeType,
                'scope_id'     => $scopeId,
                'personell_id' => $entityId,
            ],
            ['spend_ap' => $currentSpend + abs($ap)]
        );

        return true;
    }

    /**
     * Hire a personell unit (levelup).
     */
    public function hire(int $colonyId, int $personellId): bool
    {
        return $this->levelup($colonyId, $personellId);
    }

    /**
     * Fire a personell unit (leveldown).
     */
    public function fire(int $colonyId, int $personellId): bool
    {
        return $this->leveldown($colonyId, $personellId);
    }
}
