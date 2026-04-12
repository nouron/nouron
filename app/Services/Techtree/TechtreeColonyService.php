<?php

namespace App\Services\Techtree;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * TechtreeColonyService — aggregates full techtree state for a colony.
 *
 * Merges master data (buildings/researches/ships/personell) with colony-specific
 * data (level, status_points, ap_spend) into flat arrays suitable for views.
 *
 */
class TechtreeColonyService
{
    /**
     * Return all colony_buildings rows for the given colony.
     */
    public function getBuildings(int $colonyId): Collection
    {
        return DB::table('colony_buildings')->where('colony_id', $colonyId)->get();
    }

    /**
     * Return all colony_knowledge rows for the given colony.
     */
    public function getResearches(int $colonyId): Collection
    {
        return DB::table('colony_knowledge')->where('colony_id', $colonyId)->get();
    }

    /**
     * Return all colony_ships rows for the given colony.
     */
    public function getShips(int $colonyId): Collection
    {
        return DB::table('colony_ships')->where('colony_id', $colonyId)->get();
    }

    /**
     * Return all colony_personell rows for the given colony.
     */
    public function getPersonell(int $colonyId): Collection
    {
        return DB::table('colony_personell')->where('colony_id', $colonyId)->get();
    }

    /**
     * Return the full techtree for a colony, merged from master + colony tables.
     *
     * Result: [ 'building' => [...], 'research' => [...], 'ship' => [...], 'personell' => [...] ]
     * Each sub-array is keyed by entity id; values contain all master columns
     * plus level/status_points/ap_spend (defaults 0 when no colony row exists).
     */
    public function getTechtree(int $colonyId): array
    {
        return [
            'building'  => $this->_gatherTechtreeInformations($colonyId, 'building'),
            'research'  => $this->_gatherTechtreeInformations($colonyId, 'research'),
            'ship'      => $this->_gatherTechtreeInformations($colonyId, 'ship'),
            'personell' => $this->_gatherTechtreeInformations($colonyId, 'personell'),
        ];
    }

    /**
     * Merge master + colony data for a single techtree type.
     */
    private function _gatherTechtreeInformations(int $colonyId, string $type): array
    {
        [$masterTable, $colonyTable, $idKey] = match ($type) {
            'building'  => ['buildings',  'colony_buildings',  'building_id'],
            'research'  => ['knowledge',  'colony_knowledge',  'research_id'],
            'ship'      => ['ships',      'colony_ships',      'ship_id'],
            'personell' => ['personell',  'colony_personell',  'personell_id'],
            default     => throw new \InvalidArgumentException("Unknown type: $type"),
        };

        $entities = DB::table($masterTable)
            ->get()
            ->keyBy('id')
            ->map(fn($e) => (array) $e)
            ->toArray();

        $colonyEntities = DB::table($colonyTable)
            ->where('colony_id', $colonyId)
            ->get()
            ->keyBy($idKey)
            ->map(fn($e) => (array) $e)
            ->toArray();

        foreach ($entities as $id => $entity) {
            if (array_key_exists($id, $colonyEntities)) {
                $entities[$id] = array_merge($entity, $colonyEntities[$id]);
            } else {
                $entities[$id]['level']         = 0;
                $entities[$id]['status_points'] = 0;
                $entities[$id]['ap_spend']      = 0;
            }
        }

        return $entities;
    }
}
