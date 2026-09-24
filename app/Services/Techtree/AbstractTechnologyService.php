<?php

namespace App\Services\Techtree;

use App\Services\AdvisorService;
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
        protected readonly ?AdvisorService $advisorService = null,
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
     *
     * $instanceId selects one instance of an instanced building (BuildingService);
     * every other entity type has a single row per colony and ignores it.
     */
    public function getColonyEntity(int $colonyId, int $entityId, ?int $instanceId = null): ?object
    {
        return DB::table($this->colonyTable())
            ->where($this->rowKeys($colonyId, $entityId, $instanceId))
            ->first();
    }

    /**
     * Column => value pairs identifying exactly one colony-entity row. Every read
     * and write of a single row goes through this, so an instanced building is never
     * matched (or updated) across all of its instances.
     *
     * @return array<string, int>
     */
    protected function rowKeys(int $colonyId, int $entityId, ?int $instanceId = null): array
    {
        return ['colony_id' => $colonyId, $this->entityIdKey() => $entityId];
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
        if (! is_object($entity) || ! $entity->required_building_id) {
            return true;
        }

        $maxLevel = DB::table('colony_buildings')
            ->where('colony_id', $colonyId)
            ->where('building_id', $entity->required_building_id)
            ->max('level');

        if ($maxLevel === null) {
            return false;
        }

        return (int) $maxLevel >= (int) $entity->required_building_level;
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
    public function checkRequiredActionPoints(int $colonyId, int $entityId, ?int $instanceId = null): bool
    {
        if ($this->entityIdKey() === 'personell_id') {
            return true;
        }

        $entity = DB::table($this->masterTable())->find($entityId);
        if (! $entity) {
            return false;
        }

        $colonyEntity = $this->getColonyEntity($colonyId, $entityId, $instanceId);
        if (! $colonyEntity) {
            return false;
        }

        $required = $this->resolveApForLevelup($colonyId, $entityId, $entity);

        return (int) $colonyEntity->ap_spend >= $required;
    }

    /**
     * Check whether the user has enough free supply to maintain one more level
     * of the given entity.
     *
     * Supply is a capacity ceiling (SET each tick). Each entity level consumes
     * supply_cost slots. Bypassed in dev_mode.
     */
    public function checkRequiredSupplyByEntityId(int $colonyId, int $entityId, ?int $instanceId = null): bool
    {
        if (config('game.bypass.supply_checks')) {
            return true;
        }

        $entity = DB::table($this->masterTable())->find($entityId);
        if (! is_object($entity) || empty($entity->supply_cost) || (int) $entity->supply_cost === 0) {
            return true;
        }

        $colonyEntity = $this->getColonyEntity($colonyId, $entityId, $instanceId);
        if ($colonyEntity !== null && $this->firstLevelAlreadyReserved($colonyEntity)) {
            return true;
        }

        return $this->resourcesService->getFreeSupply($colonyId) >= (int) $entity->supply_cost;
    }

    /**
     * Whether the next level's supply is already part of the colony's workplaces,
     * so the supply check must not count it a second time. Only buildings reserve
     * (their first level once placed, BuildingService); other entities never do.
     */
    protected function firstLevelAlreadyReserved(object $colonyEntity): bool
    {
        return false;
    }

    /**
     * Check that a levelup would not exceed the entity's max_level cap.
     * Only enforced for buildings; all other entity types return true.
     */
    public function checkLevelUpLimit(int $colonyId, int $entityId, ?int $instanceId = null): bool
    {
        if ($this->entityIdKey() !== 'building_id') {
            return true;
        }

        $entity = DB::table($this->masterTable())->find($entityId);
        if (! is_object($entity) || ! $entity->max_level || $entity->max_level <= 0) {
            return true;
        }

        $colonyEntity = $this->getColonyEntity($colonyId, $entityId, $instanceId);
        if (! $colonyEntity) {
            return true;
        }

        return $colonyEntity->level < $entity->max_level;
    }

    /**
     * Check that the current level is above zero (cannot leveldown below 0).
     */
    public function checkLevelDownLimit(int $colonyId, int $entityId, ?int $instanceId = null): bool
    {
        $colonyEntity = $this->getColonyEntity($colonyId, $entityId, $instanceId);
        if (! $colonyEntity) {
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
     */
    protected function _invest(
        int $colonyId,
        int $entityId,
        string $changeMode = 'add',
        int $points = 1,
        bool $bypassPoolCheck = false,
        ?int $instanceId = null,
    ): bool {
        // Single source for "may this proceed, and if not why" — investBlocker() covers
        // id validation, the entity lookup, the change mode and AP availability.
        //
        // The AP gate honours game.bypass.ap_checks like every other gate in the game
        // (ColonyController, ColonyTileService, HangarService, BarService); without it the
        // techtree was the one screen that stayed locked while dev mode was on. Only the
        // *availability* of AP is bypassed, never the ap_spend threshold enforced by
        // levelupBlocker() — skipping that would level entities up with no investment.
        //
        // $bypassPoolCheck additionally skips both the pool-availability gate AND the
        // pool-locking side effect below — see investBonus() for the earmarked-AP use case.
        $bypassAp = (bool) config('game.bypass.ap_checks') || $bypassPoolCheck;

        if ($this->investBlocker($colonyId, $entityId, $changeMode, $points, $bypassPoolCheck, $instanceId) !== null) {
            return false;
        }

        $entity = DB::table($this->masterTable())->find($entityId);
        $colonyEntity = $this->getColonyEntity($colonyId, $entityId, $instanceId);

        $currentApSpend = $colonyEntity ? (int) $colonyEntity->ap_spend : 0;
        $currentStatus = $colonyEntity ? (int) $colonyEntity->status_points : 0;
        $currentLevel = $colonyEntity ? (int) $colonyEntity->level : 0;

        $apForLevelup = $this->resolveApForLevelup($colonyId, $entityId, $entity);
        $maxStatusPoints = isset($entity->max_status_points) ? (int) $entity->max_status_points : 0;

        $statusBefore = $currentStatus;
        $newApSpend = $currentApSpend;
        $newStatus = $currentStatus;

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
            'level' => $currentLevel,
            'status_points' => $newStatus,
            'ap_spend' => $newApSpend,
        ];

        $rowKeys = $this->rowKeys($colonyId, $entityId, $instanceId);

        DB::transaction(function () use ($colonyId, $entityId, $entity, $updateData, $changeMode, $statusBefore, $newStatus, $currentApSpend, $newApSpend, $bypassAp, $rowKeys) {
            DB::table($this->colonyTable())->updateOrInsert($rowKeys, $updateData);

            if ($changeMode === 'add' && ! $bypassAp) {
                // Lock the AP actually spent toward levelup so they cannot be reused in the same tick
                $apSpent = $newApSpend - $currentApSpend;
                if ($apSpent > 0 && $this->advisorService !== null) {
                    $this->advisorService->lockActionPoints($colonyId, $apSpent);
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
                if ($effectiveAp > 0 && $this->advisorService !== null) {
                    $this->advisorService->lockActionPoints($colonyId, $effectiveAp);
                }
            }
        });

        return true;
    }

    /**
     * Name the first unmet levelup requirement, or null when a levelup may proceed.
     *
     * levelup() itself only answers yes/no, which left callers — and the player — with
     * "it didn't work" and no reason. This runs the same guards in the same order and
     * returns a stable machine code, so the controller can say *why*.
     *
     * Subclasses that add their own gate override this and check theirs before calling
     * parent (see ResearchService's knowledge CC gate).
     *
     * @return string|null one of: requires_building, requires_research,
     *                     insufficient_resources, insufficient_ap_invested,
     *                     insufficient_supply, max_level
     */
    public function levelupBlocker(int $colonyId, int $entityId, ?int $instanceId = null): ?string
    {
        return match (true) {
            ! $this->checkRequiredBuildingsByEntityId($colonyId, $entityId) => 'requires_building',
            ! $this->checkRequiredResearchesByEntityId($colonyId, $entityId) => 'requires_research',
            ! $this->checkRequiredResourcesByEntityId($colonyId, $entityId) => 'insufficient_resources',
            ! $this->checkRequiredActionPoints($colonyId, $entityId, $instanceId) => 'insufficient_ap_invested',
            ! $this->checkRequiredSupplyByEntityId($colonyId, $entityId, $instanceId) => 'insufficient_supply',
            ! $this->checkLevelUpLimit($colonyId, $entityId, $instanceId) => 'max_level',
            default => null,
        };
    }

    /**
     * Name the reason an invest() call would be refused, or null when it may proceed.
     *
     * $bypassPoolCheck skips the shared-pool availability check entirely — used by
     * investBonus() for earmarked AP that never came out of the pool in the first
     * place, so its availability must never gate the injection.
     *
     * $instanceId is unused here; BuildingService uses it to check the placement of
     * the addressed instance.
     *
     * @return string|null one of: entity_not_found, insufficient_ap, invalid_mode
     */
    public function investBlocker(int $colonyId, int $entityId, string $action = 'add', int $points = 1, bool $bypassPoolCheck = false, ?int $instanceId = null): ?string
    {
        $this->validateId($colonyId);
        $this->validateId($entityId);

        if (! in_array($action, ['add', 'repair', 'remove'], true)) {
            return 'invalid_mode';
        }

        if (! DB::table($this->masterTable())->find($entityId)) {
            return 'entity_not_found';
        }

        if ($bypassPoolCheck || config('game.bypass.ap_checks')) {
            return null;
        }

        $available = $this->advisorService?->getAvailableActionPoints($colonyId) ?? 0;

        return $available < abs($points) ? 'insufficient_ap' : null;
    }

    /**
     * Level up an entity: verify all prerequisites, pay costs, increment level.
     *
     * Resets ap_spend to 0 after levelup (except for personell).
     */
    public function levelup(int $colonyId, int $entityId, ?int $instanceId = null): bool
    {
        if ($this->levelupBlocker($colonyId, $entityId, $instanceId) !== null) {
            return false;
        }

        $entity = DB::table($this->masterTable())->find($entityId);
        $colonyEntity = $this->getColonyEntity($colonyId, $entityId, $instanceId);
        $currentLevel = $colonyEntity ? (int) $colonyEntity->level : 0;
        $maxStatus = isset($entity->max_status_points) ? (int) $entity->max_status_points : 0;

        $costs = $this->getEntityCosts($entityId);
        $rowKeys = $this->rowKeys($colonyId, $entityId, $instanceId);

        DB::transaction(function () use ($colonyId, $currentLevel, $maxStatus, $costs, $rowKeys) {
            $this->resourcesService->payCosts($costs, $colonyId);

            $updateData = [
                'level' => $currentLevel + 1,
                'status_points' => $maxStatus,
            ];

            // Reset ap_spend after levelup — not applicable for personell
            if ($this->entityIdKey() !== 'personell_id') {
                $updateData['ap_spend'] = 0;
            }

            DB::table($this->colonyTable())->updateOrInsert($rowKeys, $updateData);
        });

        return true;
    }

    /**
     * Returns the AP threshold required for the next levelup.
     * Subclasses can override to implement level-dependent costs.
     */
    protected function resolveApForLevelup(int $colonyId, int $entityId, object $entity): int
    {
        return isset($entity->ap_for_levelup) ? (int) $entity->ap_for_levelup : 0;
    }

    /**
     * Name the reason a leveldown() call would be refused, or null when it may proceed.
     *
     * The default keeps the historic research/ship behaviour (levelup prerequisites
     * re-checked). BuildingService overrides it: demolishing a building level only
     * needs a level to remove.
     *
     * @return string|null one of: requires_building, requires_research,
     *                     insufficient_resources, insufficient_ap_invested, min_level
     */
    public function leveldownBlocker(int $colonyId, int $entityId, ?int $instanceId = null): ?string
    {
        return match (true) {
            ! $this->checkRequiredBuildingsByEntityId($colonyId, $entityId) => 'requires_building',
            ! $this->checkRequiredResearchesByEntityId($colonyId, $entityId) => 'requires_research',
            ! $this->checkRequiredResourcesByEntityId($colonyId, $entityId) => 'insufficient_resources',
            ! $this->checkRequiredActionPoints($colonyId, $entityId, $instanceId) => 'insufficient_ap_invested',
            ! $this->checkLevelDownLimit($colonyId, $entityId, $instanceId) => 'min_level',
            default => null,
        };
    }

    /**
     * Resource costs charged by a leveldown. Default: the entity's full cost rows
     * (historic behaviour); BuildingService charges nothing.
     */
    protected function leveldownCosts(int $entityId): Collection
    {
        return $this->getEntityCosts($entityId);
    }

    /**
     * Level down an entity: verify leveldownBlocker(), pay leveldownCosts(), decrement level.
     */
    public function leveldown(int $colonyId, int $entityId, ?int $instanceId = null): bool
    {
        if ($this->leveldownBlocker($colonyId, $entityId, $instanceId) !== null) {
            return false;
        }

        $entity = DB::table($this->masterTable())->find($entityId);
        $colonyEntity = $this->getColonyEntity($colonyId, $entityId, $instanceId);
        $currentLevel = $colonyEntity ? (int) $colonyEntity->level : 0;
        $maxStatus = isset($entity->max_status_points) ? (int) $entity->max_status_points : 0;

        $costs = $this->leveldownCosts($entityId);
        $rowKeys = $this->rowKeys($colonyId, $entityId, $instanceId);

        DB::transaction(function () use ($colonyId, $currentLevel, $maxStatus, $costs, $rowKeys) {
            if ($costs->isNotEmpty()) {
                $this->resourcesService->payCosts($costs, $colonyId);
            }

            $newLevel = $this->leveldownTargetLevel($currentLevel);
            $updateData = [
                'level' => $newLevel,
                'status_points' => $maxStatus,
            ];

            if ($this->entityIdKey() !== 'personell_id') {
                $updateData['ap_spend'] = 0;
            }

            $updateData += $this->leveldownExtraUpdate($newLevel);

            DB::table($this->colonyTable())->updateOrInsert($rowKeys, $updateData);
        });

        return true;
    }

    /**
     * The level a leveldown() leaves behind. Default: one below the current level.
     */
    protected function leveldownTargetLevel(int $currentLevel): int
    {
        return $currentLevel - 1;
    }

    /**
     * Additional columns to write when an entity is levelled down to $newLevel.
     * Default: none. BuildingService overrides this to release the tile on level 0.
     *
     * @return array<string, mixed>
     */
    protected function leveldownExtraUpdate(int $newLevel): array
    {
        return [];
    }
}
