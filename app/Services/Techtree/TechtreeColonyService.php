<?php

namespace App\Services\Techtree;

use Illuminate\Support\Facades\DB;

/**
 * TechtreeColonyService — aggregates full techtree state for a colony.
 *
 * Merges master data (buildings/researches/ships/personell) with colony-specific
 * data (level, status_points, ap_spend) into flat arrays suitable for views.
 */
class TechtreeColonyService
{
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
            'building' => $this->_gatherTechtreeInformations($colonyId, 'building'),
            'research' => $this->_gatherTechtreeInformations($colonyId, 'research'),
            'ship' => $this->_gatherTechtreeInformations($colonyId, 'ship'),
            'personell' => $this->_gatherPersonellNodes(),
        ];
    }

    /**
     * Merge master + colony data for a single techtree type.
     */
    private function _gatherTechtreeInformations(int $colonyId, string $type): array
    {
        [$masterTable, $colonyTable, $idKey] = match ($type) {
            'building' => ['buildings',  'colony_buildings',  'building_id'],
            'research' => ['researches', 'colony_researches', 'research_id'],
            'ship' => ['ships',      'colony_ships',      'ship_id'],
            default => throw new \InvalidArgumentException("Unknown type: $type"),
        };

        $entities = DB::table($masterTable)
            ->where('is_active', 1)
            ->get()
            ->keyBy('id')
            ->map(fn ($e) => (array) $e)
            ->toArray();

        $colonyEntities = DB::table($colonyTable)
            ->where('colony_id', $colonyId)
            ->orderBy('level')  // for instanced buildings: highest-level instance wins keyBy
            ->get()
            ->keyBy($idKey)
            ->map(fn ($e) => (array) $e)
            ->toArray();

        foreach ($entities as $id => $entity) {
            if (array_key_exists($id, $colonyEntities)) {
                $entities[$id] = array_merge($entity, $colonyEntities[$id]);
            } else {
                $entities[$id]['level'] = 0;
                $entities[$id]['status_points'] = 0;
                $entities[$id]['ap_spend'] = 0;
            }
        }

        return $entities;
    }

    /**
     * Personell nodes: master rows only. Advisors are hired via AdvisorService and
     * carry no techtree level, so level/status_points/ap_spend are fixed at 0.
     */
    private function _gatherPersonellNodes(): array
    {
        return DB::table('personell')
            ->where('is_active', 1)
            ->get()
            ->keyBy('id')
            ->map(fn ($e) => (array) $e + ['level' => 0, 'status_points' => 0, 'ap_spend' => 0])
            ->toArray();
    }
}
