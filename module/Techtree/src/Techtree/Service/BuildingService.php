<?php
namespace Techtree\Service;

class BuildingService extends AbstractTechnologyService
{
    protected function getEntitiesTableName()
    {
        return 'buildings';
    }

    protected function getColonyEntitiesTableName()
    {
        return 'colony_buildings';
    }

    protected function getEntityCostsTableName()
    {
        return 'building_costs';
    }

    protected function getEntityIdName()
    {
        return 'building_id';
    }

    public function invest($colonyId, $entityId, $action='add', $points=1)
    {
        return $this->_invest('construction_points', $colonyId, $entityId, $action, $points);
    }
}