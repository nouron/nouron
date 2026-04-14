<?php

namespace App\Services\Techtree;

use App\Services\Concerns\ValidatesId;
use App\Services\ResourcesService;
use App\Services\TickService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * AbstractTechnologyService — base class for all techtree services.
 *
 * Implements the shared game mechanics for buildings, researches, ships and
 * personell: prerequisite checks, AP investment, levelup/leveldown and cost
 * payment. Each concrete subclass supplies its table names and entity-id key.
 *
 * Rules:
 * - A levelup requires: required building, required research (ships only),
 *   sufficient resources, enough AP invested (ap_spend >= ap_for_levelup),
 *   and the entity is below its max_level (buildings only).
 * - Investing AP ('add' mode) only increments ap_spend up to ap_for_levelup;
 *   it does NOT lock AP from the available pool — that happens via lockActionPoints
 *   when status_points actually changes (repair/remove modes).
 * - Resources checks are deliberately always true to avoid SQLite locking issues
 *   during development; the payCosts() call in levelup/leveldown is still made.
 */
abstract class AbstractTechnologyService
{
    use ValidatesId;

    abstract protected function masterTable(): string;
    abstract protected function colonyTable(): string;
    abstract protected function costsTable(): string;
    abstract protected function entityIdKey(): string;

    public function __construct(
        protected readonly TickService $tickService,
        protected readonly ResourcesService $resourcesService,
        protected readonly ?PersonellService $personellService = null,
    ) {}

    // ── Read ─────────────────────────────────────────────────────────────────

    /**
     * Return a single master-table entity by id, or false if not found.
     *
     * @throws \InvalidArgumentException for negative ids
     */
    public function getEntity(int $id): mixed
    {
        $this->validateId($id);
        return DB::table($this->masterTable())->find($id) ?: false;
    }

    /**
     * Return all master-table entities as a Collection of stdClass objects.
     */
    public function getEntities(): Collection
    {
        return DB::table($this->masterTable())->get();
    }

    /**
     * Return the colony-specific row for a given entity, or null if absent.
     */
    public function getColonyEntity(int $colonyId, int $entityId): object|null
    {
        return DB::table($this->colonyTable())
            ->where('colony_id', $colonyId)
            ->where($this->entityIdKey(), $entityId)
            ->first();
    }

    /**
     * Return all colony-entity rows, optionally filtered by colony.
     */
    public function getColonyEntities(?int $colonyId = null): Collection
    {
        $query = DB::table($this->colonyTable());
        if ($colonyId !== null) {
            $query->where('colony_id', $colonyId);
        }
        return $query->get();
    }

    /**
     * Return all cost rows for a given entity id, or every cost row if null.
     */
    public function getEntityCosts(?int $entityId = null): Collection
    {
        $query = DB::table($this->costsTable());
        if ($entityId !== null) {
            $query->where($this->entityIdKey(), $entityId);
        }
        return $query->get();
    }

    // ── Prerequisite checks ───────────────────────────────────────────────────

    /**
     * Check whether the colony has built the required building at the required level.
     * Returns true if the entity has no required building.
     */
    public function checkRequiredBuildingsByEntityId(int $colonyId, int $entityId): bool
    {
        $entity = DB::table($this->masterTable())->find($entityId);
        if (!$entity || !$entity->required_building_id) {
            return true;
        }

        $colonyBuilding = DB::table('colony_buildings')
            ->where('colony_id', $colonyId)
            ->where('building_id', $entity->required_building_id)
            ->first();

        if (!$colonyBuilding) {
            return false;
        }

        return $colonyBuilding->level >= $entity->required_building_level;
    }

    /**
     * Check whether the colony has the required research at the required level.
     * Default implementation always returns true; only ShipService overrides this.
     */
    public function checkRequiredResearchesByEntityId(int $colonyId, int $entityId): bool
    {
        return true;
    }

    /**
     * Check whether the colony can afford the entity costs.
     *
     * Bypassed when GAME_DEV_MODE=true (default) so techs can be tested freely
     * without worrying about resource balances. Set GAME_DEV_MODE=false in .env
     * to enforce this check in production.
     */
    public function checkRequiredResourcesByEntityId(int $colonyId, int $entityId): bool
    {
        if (config('game.bypass.resource_costs')) {
            return true;
        }

        $costs = $this->getEntityCosts($entityId);
        return $costs->isEmpty() || $this->resourcesService->check($costs, $colonyId);
    }

    /**
     * Check whether enough AP has been invested for a levelup.
     *
     * Personell entities never require AP investment before hiring.
     * For all other entities the colony row's ap_spend must reach ap_for_levelup.
     */
    public function checkRequiredActionPoints(int $colonyId, int $entityId): bool
    {
        if ($this->entityIdKey() === 'personell_id') {
            return true;
        }

        $entity = DB::table($this->masterTable())->find($entityId);
        if (!$entity) {
            return false;
        }

        $colonyEntity = $this->getColonyEntity($colonyId, $entityId);
        if (!$colonyEntity) {
            return false;
        }

        return $colonyEntity->ap_spend >= $entity->ap_for_levelup;
    }

    /**
     * Check whether the user has enough free supply to maintain one more level
     * of the given entity.
     *
     * Supply is a capacity ceiling (SET each tick). Each entity level consumes
     * supply_cost slots. Bypassed in dev_mode.
     */
    public function checkRequiredSupplyByEntityId(int $colonyId, int $entityId): bool
    {
        if (config('game.bypass.supply_checks')) {
            return true;
        }

        $entity = DB::table($this->masterTable())->find($entityId);
        if (!$entity || empty($entity->supply_cost) || (int) $entity->supply_cost === 0) {
            return true;
        }

        return $this->resourcesService->getFreeSupply($colonyId) >= (int) $entity->supply_cost;
    }

    /**
     * Check that a levelup would not exceed the entity's max_level cap.
     * Only enforced for buildings; all other entity types return true.
     */
    public function checkLevelUpLimit(int $colonyId, int $entityId): bool
    {
        if ($this->entityIdKey() !== 'building_id') {
            return true;
        }

        $entity = DB::table($this->masterTable())->find($entityId);
        if (!$entity || !$entity->max_level || $entity->max_level <= 0) {
            return true;
        }

        $colonyEntity = $this->getColonyEntity($colonyId, $entityId);
        if (!$colonyEntity) {
            return true;
        }

        return $colonyEntity->level < $entity->max_level;
    }

    /**
     * Check that the current level is above zero (cannot leveldown below 0).
     */
    public function checkLevelDownLimit(int $colonyId, int $entityId): bool
    {
        $colonyEntity = $this->getColonyEntity($colonyId, $entityId);
        if (!$colonyEntity) {
            return false;
        }

        return $colonyEntity->level > 0;
    }

    // ── Core operations ───────────────────────────────────────────────────────

    /**
     * Invest action points or repair/remove status points.
     *
     * Subclasses implement this to pass the correct pointsType.
     * Must be overridden in concrete services.
     */
    abstract public function invest(int $colonyId, int $entityId, string $action = 'add', int $points = 1): bool;

    /**
     * Internal invest implementation shared by all concrete services.
     *
     * Modes:
     * - 'add':    increment ap_spend toward ap_for_levelup (no AP locking)
     * - 'repair': increment status_points toward max_status_points (locks AP)
     * - 'remove': decrement status_points toward 0 (locks AP)
     *
     * @param string $pointsType  'construction_points' or 'research_points'
     */
    protected function _invest(
        string $pointsType,
        int $colonyId,
        int $entityId,
        string $changeMode = 'add',
        int $points = 1
    ): bool {
        $this->validateId($colonyId);
        $this->validateId($entityId);

        // Verify available AP
        $availableAP = match ($pointsType) {
            'construction_points' => $this->personellService?->getConstructionPoints($colonyId) ?? 0,
            'research_points'     => $this->personellService?->getKnowledgePoints($colonyId) ?? 0,
            default               => 0,
        };

        if ($availableAP < abs($points)) {
            return false;
        }

        $entity = DB::table($this->masterTable())->find($entityId);
        if (!$entity) {
            return false;
        }

        $colonyEntity = $this->getColonyEntity($colonyId, $entityId);

        $currentApSpend     = $colonyEntity ? (int) $colonyEntity->ap_spend      : 0;
        $currentStatus      = $colonyEntity ? (int) $colonyEntity->status_points : 0;
        $currentLevel       = $colonyEntity ? (int) $colonyEntity->level         : 0;

        $apForLevelup    = isset($entity->ap_for_levelup)    ? (int) $entity->ap_for_levelup    : 0;
        $maxStatusPoints = isset($entity->max_status_points) ? (int) $entity->max_status_points : 0;

        $statusBefore = $currentStatus;
        $newApSpend   = $currentApSpend;
        $newStatus    = $currentStatus;

        switch ($changeMode) {
            case 'add':
                // Only advance ap_spend toward the levelup threshold
                $newApSpend = min($currentApSpend + abs($points), $apForLevelup);
                break;

            case 'repair':
                if ($maxStatusPoints > 0) {
                    $newStatus = min($currentStatus + abs($points), $maxStatusPoints);
                }
                break;

            case 'remove':
                $newStatus = max($currentStatus - abs($points), 0);
                break;

            default:
                return false;
        }

        $updateData = [
            'level'         => $currentLevel,
            'status_points' => $newStatus,
            'ap_spend'      => $newApSpend,
        ];

        DB::transaction(function () use ($colonyId, $entityId, $entity, $updateData, $changeMode, $statusBefore, $newStatus, $currentApSpend, $newApSpend, $pointsType) {
            DB::table($this->colonyTable())->updateOrInsert(
                ['colony_id' => $colonyId, $this->entityIdKey() => $entityId],
                $updateData
            );

            $apType = ($pointsType === 'research_points') ? 'knowledge' : 'construction';

            if ($changeMode === 'add') {
                // Lock the AP actually spent toward levelup so they cannot be reused in the same tick
                $apSpent = $newApSpend - $currentApSpend;
                if ($apSpent > 0 && $this->personellService !== null) {
                    $this->personellService->lockActionPoints($apType, $colonyId, $apSpent);
                }
            }

            // For repair mode, deduct proportional costs per status point gained
            if ($changeMode === 'repair') {
                $statusGained = $newStatus - $statusBefore;
                if ($statusGained > 0) {
                    $maxStatusPoints = isset($entity->max_status_points) ? (int) $entity->max_status_points : 0;
                    if ($maxStatusPoints > 0) {
                        $costs = DB::table($this->costsTable())
                            ->where($this->entityIdKey(), $entityId)
                            ->get();
                        foreach ($costs as $cost) {
                            $repairCost = (int) floor($cost->amount / $maxStatusPoints) * $statusGained;
                            if ($repairCost > 0) {
                                $this->resourcesService->decreaseAmount($colonyId, $cost->resource_id, $repairCost);
                            }
                        }
                    }
                }
            }

            // Lock AP when status_points changed (repair or remove)
            if (in_array($changeMode, ['repair', 'remove'])) {
                $effectiveAp = abs($newStatus - $statusBefore);
                if ($effectiveAp > 0 && $this->personellService !== null) {
                    $this->personellService->lockActionPoints($apType, $colonyId, $effectiveAp);
                }
            }
        });

        return true;
    }

    /**
     * Level up an entity: verify all prerequisites, pay costs, increment level.
     *
     * Resets ap_spend to 0 after levelup (except for personell).
     */
    public function levelup(int $colonyId, int $entityId): bool
    {
        if (!$this->checkRequiredBuildingsByEntityId($colonyId, $entityId)) {
            return false;
        }
        if (!$this->checkRequiredResearchesByEntityId($colonyId, $entityId)) {
            return false;
        }
        if (!$this->checkRequiredResourcesByEntityId($colonyId, $entityId)) {
            return false;
        }
        if (!$this->checkRequiredActionPoints($colonyId, $entityId)) {
            return false;
        }
        if (!$this->checkRequiredSupplyByEntityId($colonyId, $entityId)) {
            return false;
        }
        if (!$this->checkLevelUpLimit($colonyId, $entityId)) {
            return false;
        }

        $entity       = DB::table($this->masterTable())->find($entityId);
        $colonyEntity = $this->getColonyEntity($colonyId, $entityId);
        $currentLevel = $colonyEntity ? (int) $colonyEntity->level : 0;
        $maxStatus    = isset($entity->max_status_points) ? (int) $entity->max_status_points : 0;

        $costs = $this->getEntityCosts($entityId);

        DB::transaction(function () use ($colonyId, $entityId, $entity, $currentLevel, $maxStatus, $costs) {
            $this->resourcesService->payCosts($costs, $colonyId);

            $updateData = [
                'level'         => $currentLevel + 1,
                'status_points' => $maxStatus,
            ];

            // Reset ap_spend after levelup — not applicable for personell
            if ($this->entityIdKey() !== 'personell_id') {
                $updateData['ap_spend'] = 0;
            }

            DB::table($this->colonyTable())->updateOrInsert(
                ['colony_id' => $colonyId, $this->entityIdKey() => $entityId],
                $updateData
            );
        });

        return true;
    }

    /**
     * Level down an entity: verify prerequisites + min-level limit, pay costs, decrement level.
     */
    public function leveldown(int $colonyId, int $entityId): bool
    {
        if (!$this->checkRequiredBuildingsByEntityId($colonyId, $entityId)) {
            return false;
        }
        if (!$this->checkRequiredResearchesByEntityId($colonyId, $entityId)) {
            return false;
        }
        if (!$this->checkRequiredResourcesByEntityId($colonyId, $entityId)) {
            return false;
        }
        if (!$this->checkRequiredActionPoints($colonyId, $entityId)) {
            return false;
        }
        if (!$this->checkLevelDownLimit($colonyId, $entityId)) {
            return false;
        }

        $entity       = DB::table($this->masterTable())->find($entityId);
        $colonyEntity = $this->getColonyEntity($colonyId, $entityId);
        $currentLevel = $colonyEntity ? (int) $colonyEntity->level : 0;
        $maxStatus    = isset($entity->max_status_points) ? (int) $entity->max_status_points : 0;

        $costs = $this->getEntityCosts($entityId);

        DB::transaction(function () use ($colonyId, $entityId, $currentLevel, $maxStatus, $costs) {
            $this->resourcesService->payCosts($costs, $colonyId);

            $updateData = [
                'level'         => $currentLevel - 1,
                'status_points' => $maxStatus,
            ];

            if ($this->entityIdKey() !== 'personell_id') {
                $updateData['ap_spend'] = 0;
            }

            DB::table($this->colonyTable())->updateOrInsert(
                ['colony_id' => $colonyId, $this->entityIdKey() => $entityId],
                $updateData
            );
        });

        return true;
    }
}
