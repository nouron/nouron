<?php

namespace App\Services\Techtree;

use App\Enums\BuildingId;
use App\Services\ResourcesService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * BuildingService — manages colony buildings.
 *
 * Buildings require construction AP (engineers) to be invested before a
 * levelup can be triggered. Level is capped by building.max_level (if set).
 *
 * Every operation addresses exactly one colony_buildings row via
 * (colony_id, building_id, instance_id). A null $instanceId means instance 1 —
 * the only instance of a non-instanced building. Callers acting on instanced
 * buildings check the instance first (instanceBlocker()).
 */
class BuildingService extends AbstractTechnologyService
{
    protected function masterTable(): string
    {
        return 'buildings';
    }

    protected function colonyTable(): string
    {
        return 'colony_buildings';
    }

    protected function costsTable(): string
    {
        return 'building_costs';
    }

    protected function entityIdKey(): string
    {
        return 'building_id';
    }

    /**
     * @return array<string, int>
     */
    protected function rowKeys(int $colonyId, int $entityId, ?int $instanceId = null): array
    {
        return parent::rowKeys($colonyId, $entityId) + ['instance_id' => $instanceId ?? 1];
    }

    /**
     * Name the reason an order cannot be tied to one instance, or null if it can.
     *
     * An explicit $instanceId must belong to this colony and building (a foreign or
     * unknown id is 'instance_not_found'). Without one, the order is only
     * unambiguous while the colony has at most one row of this building —
     * otherwise 'instance_required'.
     */
    public function instanceBlocker(int $colonyId, int $buildingId, ?int $instanceId): ?string
    {
        $rows = DB::table($this->colonyTable())
            ->where('colony_id', $colonyId)
            ->where('building_id', $buildingId);

        if ($instanceId !== null) {
            return $rows->where('instance_id', $instanceId)->exists() ? null : 'instance_not_found';
        }

        return $rows->count() > 1 ? 'instance_required' : null;
    }

    /**
     * Every placed instance of the colony's buildings, grouped by building_id and
     * ordered by instance_id — a row counts as placed under the same rule as
     * isPlaced(): the anchored Command Center always, any other row only with a tile.
     *
     * @return array<int, list<array{instance_id: int, level: int, max_level: int|null, status_points: int, max_status_points: int, q: int|null, r: int|null}>>
     */
    public function placedInstances(int $colonyId): array
    {
        $rows = DB::table($this->colonyTable())
            ->join($this->masterTable(), 'colony_buildings.building_id', '=', 'buildings.id')
            ->where('colony_buildings.colony_id', $colonyId)
            ->where(fn ($q) => $q
                ->whereNotNull('colony_buildings.tile_x')
                ->orWhere('colony_buildings.building_id', BuildingId::CommandCenter->value))
            ->orderBy('colony_buildings.building_id')
            ->orderBy('colony_buildings.instance_id')
            ->select(
                'colony_buildings.building_id',
                'colony_buildings.instance_id',
                'colony_buildings.level',
                'colony_buildings.status_points',
                'colony_buildings.tile_x',
                'colony_buildings.tile_y',
                'buildings.max_level',
                'buildings.max_status_points',
            )
            ->get();

        $instances = [];
        foreach ($rows as $row) {
            $instances[(int) $row->building_id][] = [
                'instance_id' => (int) $row->instance_id,
                'level' => (int) $row->level,
                'max_level' => $row->max_level !== null ? (int) $row->max_level : null,
                'status_points' => (int) $row->status_points,
                'max_status_points' => (int) $row->max_status_points,
                'q' => $row->tile_x !== null ? (int) $row->tile_x : null,
                'r' => $row->tile_y !== null ? (int) $row->tile_y : null,
            ];
        }

        return $instances;
    }

    /**
     * Whether a building instance stands on a tile. The Command Center is anchored
     * to the colony centre and never carries tile coordinates (OnboardingService
     * seeds it with tile_x NULL), so it always counts as placed.
     */
    public function isPlaced(int $colonyId, int $buildingId, ?int $instanceId = null): bool
    {
        if ($buildingId === BuildingId::CommandCenter->value) {
            return true;
        }

        return $this->getColonyEntity($colonyId, $buildingId, $instanceId)?->tile_x !== null;
    }

    /**
     * A building must be placed on a tile before it can gain a level — the
     * techtree must not raise a never-placed level-0 row to level 1.
     */
    public function levelupBlocker(int $colonyId, int $entityId, ?int $instanceId = null): ?string
    {
        if (! $this->isPlaced($colonyId, $entityId, $instanceId)) {
            return 'not_placed';
        }

        return parent::levelupBlocker($colonyId, $entityId, $instanceId);
    }

    /**
     * Investing toward a level ('add') needs a placed building, like levelup().
     * Repair/remove only touch status points and keep the shared rules.
     */
    public function investBlocker(int $colonyId, int $entityId, string $action = 'add', int $points = 1, bool $bypassPoolCheck = false, ?int $instanceId = null): ?string
    {
        $this->validateId($colonyId);
        $this->validateId($entityId);

        if ($action === 'add'
            && DB::table($this->masterTable())->find($entityId)
            && ! $this->isPlaced($colonyId, $entityId, $instanceId)) {
            return 'not_placed';
        }

        return parent::investBlocker($colonyId, $entityId, $action, $points, $bypassPoolCheck, $instanceId);
    }

    /**
     * Demolishing a building level (Rückbau) only needs a level to remove — or a
     * placed level-0 construction site to cancel (Bauabbruch): no levelup
     * prerequisites, no invested AP, no resource check. Its workplace effect follows
     * from the lower level alone (ResourcesService::buildingWorkplaces()). A level-0
     * row without a tile has nothing to remove ('not_placed').
     */
    public function leveldownBlocker(int $colonyId, int $entityId, ?int $instanceId = null): ?string
    {
        $row = $this->getColonyEntity($colonyId, $entityId, $instanceId);

        if ($row !== null
            && $entityId !== BuildingId::CommandCenter->value
            && (int) $row->level === 0
            && $row->tile_x === null) {
            return 'not_placed';
        }

        return $this->checkLevelDownLimit($colonyId, $entityId, $instanceId) ? null : 'min_level';
    }

    /**
     * What a Rückbau of this instance would change, for the confirmation dialog —
     * or null when leveldownBlocker() refuses it. Computed by running the real
     * leveldown() inside a transaction that is always rolled back, so every number
     * is the one the Rückbau actually produces:
     *   freed_workplaces — free supply gained immediately
     *   capacity_loss    — supply cap lost when the next Sol recalculates it
     *                      (ResourcesService::calculateSupplyCap(), housing/CC only)
     *   homeless_now     — homeless colonists at the next Sol without the Rückbau
     *   homeless_after   — homeless colonists at the next Sol with the Rückbau
     * Both use the recalculated cap, not the stored user_resources.supply: the stored
     * cap lags behind housing changes within a Sol, and mixing the two would make the
     * comparison show that lag instead of the Rückbau's effect.
     *
     * @return array{building_id: int, instance_id: int, current_level: int, new_level: int, construction_cancelled: bool, ap_forfeited: int, freed_workplaces: int, capacity_loss: int, homeless_now: int, homeless_after: int, tile_released: bool, released_tile: array{q: int, r: int}|null}|null
     */
    public function leveldownPreview(int $colonyId, int $buildingId, int $instanceId): ?array
    {
        if ($this->leveldownBlocker($colonyId, $buildingId, $instanceId) !== null) {
            return null;
        }

        $before = $this->getColonyEntity($colonyId, $buildingId, $instanceId);
        $freeBefore = $this->resourcesService->getFreeSupply($colonyId);
        $capBefore = $this->resourcesService->calculateSupplyCap($colonyId);
        $homelessNow = $this->resourcesService->colonistStatus($colonyId, $capBefore)['homeless'];

        DB::beginTransaction();
        try {
            $this->leveldown($colonyId, $buildingId, $instanceId);
            $after = $this->getColonyEntity($colonyId, $buildingId, $instanceId);
            $freeAfter = $this->resourcesService->getFreeSupply($colonyId);
            $capAfter = $this->resourcesService->calculateSupplyCap($colonyId);
            $homelessAfter = $this->resourcesService->colonistStatus($colonyId, $capAfter)['homeless'];
        } finally {
            DB::rollBack();
        }

        $tileReleased = $before->tile_x !== null && $after?->tile_x === null;

        return [
            'building_id' => $buildingId,
            'instance_id' => $instanceId,
            'current_level' => (int) $before->level,
            'new_level' => (int) $after?->level,
            'construction_cancelled' => (int) $before->level === 0,
            'ap_forfeited' => (int) $before->ap_spend,
            'freed_workplaces' => $freeAfter - $freeBefore,
            'capacity_loss' => $capBefore - $capAfter,
            'homeless_now' => $homelessNow,
            'homeless_after' => $homelessAfter,
            'tile_released' => $tileReleased,
            'released_tile' => $tileReleased ? ['q' => (int) $before->tile_x, 'r' => (int) $before->tile_y] : null,
        ];
    }

    /**
     * Rückbau charges no resources (and in particular no supply).
     */
    protected function leveldownCosts(int $entityId): Collection
    {
        return collect();
    }

    /**
     * A placed level-0 building already reserves the workplaces of its first level
     * (GDD §6 "Supply als Bau-Gate", A14) — the 0 → 1 level-up is never supply-checked.
     */
    protected function firstLevelAlreadyReserved(object $colonyEntity): bool
    {
        return ResourcesService::reservesFirstLevel($colonyEntity);
    }

    /**
     * The Command Center is the colony's anchor — it can never be levelled down
     * below 1. Every other building may go down to 0; a placed level-0 construction
     * site may be cancelled (Bauabbruch, Owner decision 2026-09-24) and stays on 0.
     */
    public function checkLevelDownLimit(int $colonyId, int $entityId, ?int $instanceId = null): bool
    {
        $row = $this->getColonyEntity($colonyId, $entityId, $instanceId);
        if ($row === null) {
            return false;
        }

        if ($entityId === BuildingId::CommandCenter->value) {
            return (int) $row->level > 1;
        }

        return (int) $row->level > 0 || $row->tile_x !== null;
    }

    /**
     * Cancelling a level-0 construction site keeps it on level 0.
     */
    protected function leveldownTargetLevel(int $currentLevel): int
    {
        return max(0, $currentLevel - 1);
    }

    /**
     * A building levelled down to 0 is taken off its tile (A14 Owner decision
     * 2026-09-24): it is "not placed" again, so the tile is free and it no longer
     * reserves the workplaces of its first level (ResourcesService::reservesFirstLevel()).
     * Harvester transit/outage state is tied to the tile and cleared with it.
     *
     * @return array<string, mixed>
     */
    protected function leveldownExtraUpdate(int $newLevel): array
    {
        if ($newLevel > 0) {
            return [];
        }

        return [
            'tile_x' => null,
            'tile_y' => null,
            'pending_until_tick' => null,
            'instability_outage_until_tick' => null,
        ];
    }

    /**
     * Invest construction points into a building (add AP, repair, or remove damage).
     */
    public function invest(int $colonyId, int $entityId, string $action = 'add', int $points = 1, ?int $instanceId = null): bool
    {
        return $this->_invest($colonyId, $entityId, $action, $points, instanceId: $instanceId);
    }
}
