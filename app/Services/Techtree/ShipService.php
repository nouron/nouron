<?php

namespace App\Services\Techtree;

use Illuminate\Support\Facades\DB;

/**
 * ShipService — manages colony ship classes.
 *
 * Ships require construction AP (engineers) and may additionally require a
 * specific research to be at a minimum level before a levelup.
 *
 * Migrated from Techtree\Service\ShipService (Laminas).
 */
class ShipService extends AbstractTechnologyService
{
    protected function masterTable(): string  { return 'ships'; }
    protected function colonyTable(): string  { return 'colony_ships'; }
    protected function costsTable(): string   { return 'ship_costs'; }
    protected function entityIdKey(): string  { return 'ship_id'; }

    /**
     * Invest construction points into a ship class (add AP, repair, or remove damage).
     */
    public function invest(int $colonyId, int $entityId, string $action = 'add', int $points = 1): bool
    {
        return $this->_invest('construction_points', $colonyId, $entityId, $action, $points);
    }

    /**
     * Additionally check that the colony has the required research at or above
     * the required_research_level defined on the ship master record.
     */
    public function checkRequiredResearchesByEntityId(int $colonyId, int $entityId): bool
    {
        $ship = DB::table('ships')->find($entityId);
        if (!$ship || !$ship->required_research_id) {
            return true;
        }

        $colonyResearch = DB::table('colony_researches')
            ->where('colony_id', $colonyId)
            ->where('research_id', $ship->required_research_id)
            ->first();

        if (!$colonyResearch) {
            return false;
        }

        return $colonyResearch->level >= $ship->required_research_level;
    }
}
