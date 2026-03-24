<?php

namespace App\Services\Techtree;

use App\Services\Concerns\ValidatesId;
use App\Services\ResourcesService;
use App\Services\TickService;
use Illuminate\Support\Facades\DB;

/**
 * PersonellService — manages colony personell (engineers, scientists, pilots, traders).
 *
 * Personell determines the available Action Points per tick:
 * - Engineers  → construction AP (used by buildings and ships)
 * - Scientists → research AP    (used by researches)
 * - Pilots     → navigation AP  (not yet implemented)
 *
 * Formula: totalAP = level * DEFAULT_ACTIONPOINTS + DEFAULT_ACTIONPOINTS
 * Available AP = totalAP − locked AP for the current tick.
 *
 * Personell cannot receive AP investments (invest() always returns false).
 * Use hire()/fire() which delegate to levelup()/leveldown().
 *
 * Migrated from Techtree\Service\PersonellService (Laminas).
 */
class PersonellService extends AbstractTechnologyService
{
    const PERSONELL_ID_ENGINEER  = 35;
    const PERSONELL_ID_SCIENTIST = 36;
    const PERSONELL_ID_PILOT     = 89;
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
     * Return the total AP for a given type based on the personell level.
     * Formula: level * DEFAULT_ACTIONPOINTS + DEFAULT_ACTIONPOINTS
     */
    public function getTotalActionPoints(string $type, int $colonyId): int
    {
        $entityId = match (strtolower($type)) {
            'construction' => self::PERSONELL_ID_ENGINEER,
            'research'     => self::PERSONELL_ID_SCIENTIST,
            'navigation'   => self::PERSONELL_ID_PILOT,
            default        => null,
        };

        if (!$entityId) {
            return 0;
        }

        $personell = $this->getColonyEntity($colonyId, $entityId);
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
     * Navigation points are not yet implemented.
     */
    public function getNavigationPoints(int $colonyId): int
    {
        return 0;
    }

    /**
     * Return available AP (total − locked in current tick) for a given type.
     */
    public function getAvailableActionPoints(string $type, int $scopeId): int
    {
        $this->validateId($scopeId);

        if (strtolower($type) === 'navigation') {
            return 0;
        }

        $entityId = match (strtolower($type)) {
            'construction' => self::PERSONELL_ID_ENGINEER,
            'research'     => self::PERSONELL_ID_SCIENTIST,
            default        => null,
        };

        if (!$entityId) {
            return 0;
        }

        $totalAP     = $this->getTotalActionPoints($type, $scopeId);
        $currentTick = $this->tickService->getTickCount();

        $locked = DB::table('locked_actionpoints')
            ->where('tick', $currentTick)
            ->where('colony_id', $scopeId)
            ->where('personell_id', $entityId)
            ->value('spend_ap') ?? 0;

        return $totalAP - (int) $locked;
    }

    /**
     * Lock (spend) AP for a colony in the current tick.
     *
     * Adds to any existing locked amount for the tick.
     */
    public function lockActionPoints(string $type, int $colonyId, int $ap): bool
    {
        $this->validateId($colonyId);

        $tick     = $this->tickService->getTickCount();
        $entityId = match (strtolower($type)) {
            'construction' => self::PERSONELL_ID_ENGINEER,
            'research'     => self::PERSONELL_ID_SCIENTIST,
            default        => null,
        };

        if (!$entityId) {
            return false;
        }

        $existing = DB::table('locked_actionpoints')
            ->where(['tick' => $tick, 'colony_id' => $colonyId, 'personell_id' => $entityId])
            ->first();

        $currentSpend = $existing ? (int) $existing->spend_ap : 0;

        DB::table('locked_actionpoints')->updateOrInsert(
            ['tick' => $tick, 'colony_id' => $colonyId, 'personell_id' => $entityId],
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
