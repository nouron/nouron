<?php

namespace App\Services\Techtree;

/**
 * BuildingService — manages colony buildings.
 *
 * Buildings require construction AP (engineers) to be invested before a
 * levelup can be triggered. Level is capped by building.max_level (if set).
 *
 * Migrated from Techtree\Service\BuildingService (Laminas).
 */
class BuildingService extends AbstractTechnologyService
{
    protected function masterTable(): string  { return 'buildings'; }
    protected function colonyTable(): string  { return 'colony_buildings'; }
    protected function costsTable(): string   { return 'building_costs'; }
    protected function entityIdKey(): string  { return 'building_id'; }

    /**
     * Invest construction points into a building (add AP, repair, or remove damage).
     */
    public function invest(int $colonyId, int $entityId, string $action = 'add', int $points = 1): bool
    {
        return $this->_invest('construction_points', $colonyId, $entityId, $action, $points);
    }
}
