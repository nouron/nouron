<?php

namespace App\Services\Techtree;

/**
 * The one rule for a techtree entity's building prerequisites (required_building_* and
 * required_building2_*): used by the techtree display (locked/available), every level-up
 * (AbstractTechnologyService::checkRequiredBuildingsByEntityId) and silent research,
 * which waives the sciencelab.
 */
final class BuildingPrerequisites
{
    private const SLOTS = [
        ['required_building_id', 'required_building_level'],
        ['required_building2_id', 'required_building2_level'],
    ];

    /**
     * @param  object|array<string, mixed>  $entity  master row or techtree array
     * @param  callable(int): int  $levelOf  current level of a building id (0 if absent)
     * @param  list<int>  $waive  building ids whose requirement does not count
     */
    public static function met(object|array $entity, callable $levelOf, array $waive = []): bool
    {
        $fields = (array) $entity;

        foreach (self::SLOTS as [$idField, $levelField]) {
            $buildingId = (int) ($fields[$idField] ?? 0);
            if ($buildingId === 0 || in_array($buildingId, $waive, true)) {
                continue;
            }
            if ($levelOf($buildingId) < (int) ($fields[$levelField] ?? 1)) {
                return false;
            }
        }

        return true;
    }
}
