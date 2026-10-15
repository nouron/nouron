<?php

namespace App\Services;

use App\Enums\BuildingId;
use Illuminate\Support\Facades\DB;

/**
 * Path-building build gate (GDD §16 "Slot-System", Gate-Logik; server rule since
 * Owner decision 2026-10-14): a NEW path building may only be placed while
 *   placed path buildings < CC level − game.path_building_gate.cc_level_offset.
 *
 * "Placed" means the building occupies a tile (tile_x NOT NULL) — a path building
 * levelled down to 0 leaves its tile (BuildingService::leveldownExtraUpdate()) and
 * counts as a new placement when it is put down again. Path buildings that already
 * stand are never affected, e.g. after a CC Rückbau.
 */
class PathBuildingGateService
{
    /** sciencelab, hangar, bar — the three path buildings (ascending id = GDD tie-break order). */
    public const PATH_BUILDING_IDS = [
        BuildingId::Sciencelab->value,
        BuildingId::Hangar->value,
        BuildingId::Bar->value,
    ];

    public static function isPathBuilding(int $buildingId): bool
    {
        return in_array($buildingId, self::PATH_BUILDING_IDS, true);
    }

    /** Number of path buildings currently standing on a tile. */
    public function placedCount(int $colonyId): int
    {
        return DB::table('colony_buildings')
            ->where('colony_id', $colonyId)
            ->whereIn('building_id', self::PATH_BUILDING_IDS)
            ->whereNotNull('tile_x')
            ->count();
    }

    /** CC level at which one more path building may be placed, given the current count. */
    public function requiredCcLevelForNext(int $colonyId): int
    {
        return $this->placedCount($colonyId) + 1 + (int) config('game.path_building_gate.cc_level_offset', 1);
    }

    /**
     * CC level the placement of $buildingId still waits for, or null when the gate
     * lets it through (non-path building, already standing, or below the cap).
     */
    public function blockingCcLevel(int $colonyId, int $buildingId, int $ccLevel): ?int
    {
        if (! self::isPathBuilding($buildingId) || $this->isPlaced($colonyId, $buildingId)) {
            return null;
        }

        $required = $this->requiredCcLevelForNext($colonyId);

        return $ccLevel < $required ? $required : null;
    }

    private function isPlaced(int $colonyId, int $buildingId): bool
    {
        return DB::table('colony_buildings')
            ->where('colony_id', $colonyId)
            ->where('building_id', $buildingId)
            ->whereNotNull('tile_x')
            ->exists();
    }
}
