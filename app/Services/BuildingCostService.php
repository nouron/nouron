<?php

namespace App\Services;

use App\Enums\BuildingId;

/**
 * Regolith/Werkstoffe costs of colony buildings — the single place every
 * consumer (placement, invest, build list, sidebar, onboarding hints) reads.
 *
 * Owner rule 2026-09-25 (T9): the Regolith a building costs is always paid in
 * full when it is placed. Placing therefore charges the erect cost
 * (config/buildings.php `build_cost`) PLUS the level-up Regolith of the 0 -> 1
 * step (GDD §13.7 "auch der Sprung 0→1 nach der Errichtung") in one payment;
 * the 0 -> 1 invest cycle of a placed level-0 site costs no Regolith afterwards.
 */
class BuildingCostService
{
    public const RES_REGOLITH = 3;

    /**
     * One-time erect cost, as [resource_id => amount]. CC + Harvester have none.
     *
     * @return array<int, int>
     */
    public function erectCost(int $buildingId): array
    {
        $cfg = collect(config('buildings'))->firstWhere('id', $buildingId);

        return array_map('intval', $cfg['build_cost'] ?? []);
    }

    /**
     * Regolith of the level-up to $targetLevel. CommandCenter scales as
     * target_level × cc_upgrade_regolith_per_level; Harvester is free (bootstrap);
     * all others pay the flat config('game.build.levelup_regolith_flat'),
     * independent of build_cost.
     */
    public function levelupRegolith(int $buildingId, int $targetLevel): int
    {
        if ($buildingId === BuildingId::Harvester->value) {
            return 0;
        }

        if ($buildingId === BuildingId::CommandCenter->value) {
            $perLevel = (int) (collect(config('buildings'))->firstWhere('id', $buildingId)['cc_upgrade_regolith_per_level'] ?? 30);

            return $targetLevel * $perLevel;
        }

        return (int) config('game.build.levelup_regolith_flat', 25);
    }

    /** Regolith of the 0 -> 1 step, prepaid on placement. */
    public function firstLevelRegolith(int $buildingId): int
    {
        return $this->levelupRegolith($buildingId, 1);
    }

    /**
     * Full cost of placing a building: erect cost plus the first-level Regolith,
     * as [resource_id => amount] (zero amounts omitted). The Harvester is placed
     * free of charge (bootstrap / entitlement, GDD §4c).
     *
     * @return array<int, int>
     */
    public function placementCost(int $buildingId): array
    {
        if ($buildingId === BuildingId::Harvester->value) {
            return [];
        }

        $cost = $this->erectCost($buildingId);
        $firstLevel = $this->firstLevelRegolith($buildingId);
        if ($firstLevel > 0) {
            $cost[self::RES_REGOLITH] = ($cost[self::RES_REGOLITH] ?? 0) + $firstLevel;
        }

        return array_filter($cost, fn (int $amount) => $amount > 0);
    }

    /**
     * Whether the 0 -> 1 level-up of this colony building row was already paid on
     * placement: a placed site on level 0. (A building levelled down to 0 is taken
     * off its tile, decay never drops below 1 — so a placed level-0 row is always
     * a fresh, fully paid placement.)
     */
    public static function firstLevelPrepaid(object $colonyBuilding): bool
    {
        return (int) $colonyBuilding->level === 0 && $colonyBuilding->tile_x !== null;
    }

    /** Regolith still due when the next level-up cycle of this colony building row starts. */
    public function levelupRegolithDue(object $colonyBuilding): int
    {
        if (self::firstLevelPrepaid($colonyBuilding)) {
            return 0;
        }

        return $this->levelupRegolith((int) $colonyBuilding->building_id, (int) $colonyBuilding->level + 1);
    }
}
