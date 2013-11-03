<?php
namespace Techtree\Service;

class ShipService extends AbstractTechnologyService
{
    protected function getEntitiesTableName()
    {
        return 'ships';
    }
    
    protected function getColonyEntitiesTableName()
    {
        return 'colony_ships';
    }
    
    protected function getEntityCostsTableName()
    {
        return 'ship_costs';
    }
    
    protected function getEntityIdName()
    {
        return 'ship_id';
    }

    /**
     * Check if required research on required level exists
     *
     * @param  integer  $entityId
     * @param  integer  $colonyId
     * @return boolean
     * @throws \Techtree\Model\Exception if invalid parameter(s)
     */
    public function checkRequiredResearchesByEntityId($colonyId, $entityId)
    {
        $this->_validateId($entityId);
        $this->_validateId($colonyId);

        $poss = $this->getTable('colony_researches')->fetchAll()->getArrayCopy('research_id');
        $ships = $this->getEntities()->getArrayCopy('id');
        $ship  = $ships[$entityId];
        $required_research_id = $ship['required_research_id'];
        if (empty($required_research_id)) {
            return True;
        } else if (!isset($poss[$required_research_id])) {
            return False;
        } else {
            $required_research = $poss[$required_research_id];
            return ($required_research['level'] >= $ship['required_research_level']);
        }
    }

    public function invest($colonyId, $entityId, $action='add', $points=1)
    {
        return $this->_invest('construction_points', $colonyId, $entityId, $action, $points);
    }
}