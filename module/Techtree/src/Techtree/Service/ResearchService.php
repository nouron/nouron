<?php
namespace Techtree\Service;

class ResearchService extends AbstractTechnologyService
{
    protected function getEntitiesTableName()
    {
        return 'researches';
    }
    
    protected function getColonyEntitiesTableName()
    {
        return 'colony_researches';
    }
    
    protected function getEntityCostsTableName()
    {
        return 'research_costs';
    }
    
    protected function getEntityIdName()
    {
        return 'research_id';
    }

    public function invest($colonyId, $entityId, $action='add', $points=1)
    {
        return $this->_invest('research_points', $colonyId, $entityId, $action, $points);
    }
}