<?php
namespace Techtree\Service;

abstract class AbstractTechnologyService extends \Nouron\Service\AbstractService implements TechnologyServiceInterface
{

    abstract protected function getEntitiesTableName();

    abstract protected function getColonyEntitiesTableName();

    abstract protected function getEntityCostsTableName();

    abstract protected function getEntityIdName();

    /**
     * @see \Techtree\Service\TechnologyServiceInterface::getEntity()
     * @param numeric $entityId
     * @return ResultSet
     */
    public function getEntity($entityId)
    {
        $this->_validateId($entityId);
        #$where = array('id' => $entityId);
        return $this->getTable($this->getEntitiesTableName())->getEntity($entityId);
    }

    /**
     * @see \Techtree\Service\TechnologyServiceInterface::getEntities()
     * @return ResultSet
     */
    public function getEntities()
    {
        return $this->getTable($this->getEntitiesTableName())->fetchAll();
    }

    /**
     * @see \Techtree\Service\TechnologyServiceInterface::getColonyEntity()
     * @param  numeric $colonyId
     * @param  numeric $entityId
     * @return ResultSet
     */
    public function getColonyEntity($colonyId, $entityId)
    {
        $where = array(
            'colony_id' => $colonyId,
            $this->getEntityIdName() => $entityId
        );
        return $this->getTable($this->getColonyEntitiesTableName())->getEntity($where);
    }

    /**
     * @see \Techtree\Service\TechnologyServiceInterface::getColonyEntities()
     * @param  numeric|null $entityId OPTIONAL
     * @return ResultSet
     */
    public function getColonyEntities($colonyId = null)
    {
        $where = is_numeric($colonyId) ? array('colony_id' => $colonyId) : null;
        return $this->getTable($this->getColonyEntitiesTableName())->fetchAll($where);
    }

    /**
     * @see \Techtree\Service\TechnologyServiceInterface::getEntityCosts()
     * @param  numeric|null $entityId OPTIONAL
     * @return ResultSet
     */
    public function getEntityCosts($entityId = null)
    {
        $where = is_numeric($entityId) ? array($this->getEntityIdName() => $entityId) : null;
        return $this->getTable($this->getEntityCostsTableName())->fetchAll($where);
    }

    /**
     * Check if required building on required level exists
     *
     * @see \Techtree\Service\TechnologyServiceInterface::checkRequiredBuildingsByEntityId()
     * @param  integer  $entityId
     * @param  integer  $colonyId
     * @return boolean
     * @throws \Techtree\Model\Exception if invalid parameter(s)
     */
    public function checkRequiredBuildingsByEntityId( $colonyId, $entityId)
    {
        $this->_validateId($entityId);
        $this->_validateId($colonyId);

        $colonyBuildings = $this->getTable('colony_buildings')
                                ->fetchAll(array('colony_id' => $colonyId))
                                ->getArrayCopy('building_id');
        $entities = $this->getEntities()->getArrayCopy('id');
        $entity   = $entities[$entityId];
        $requiredBuildingId = $entity['required_building_id'];
        if (empty($requiredBuildingId)) {
            return True;
        } else if (!isset($colonyBuildings[$requiredBuildingId])) {
            $this->getLogger()->log(\Zend\Log\Logger::INFO, $requiredBuildingId);
            $this->getLogger()->log(\Zend\Log\Logger::INFO, $colonyBuildings);

            return False;
        } else {
            $requiredBuilding = $colonyBuildings[$requiredBuildingId];
            return ($requiredBuilding['level'] >= $entity['required_building_level']);
        }
    }

    /**
     * (non-PHPdoc)
     * @see \Techtree\Service\TechnologyServiceInterface::checkRequiredResearchesByEntityId()
     */
    public function checkRequiredResearchesByEntityId($colonyId, $entityId)
    {
        // no required researches - just fullfill interface
        return true;
    }

    /**
     *
     * @param numeric $entityId
     * @param numeric $colonyId
     * @return boolean
     * @throws \Techtree\Model\Exception if invalid parameter(s)
     */
    public function checkRequiredResourcesByEntityId($colonyId, $entityId)
    {
        $this->_validateId($entityId);
        $this->_validateId($colonyId);

        $costs = $this->getEntityCosts($entityId);
        return $this->getService('resources')->check($costs, $colonyId);
    }

    /**
     *
     * 'repair' : set change mode to 'status_ap' and set a positive $cp number
     * 'destroy': set change mode to 'status_ap' and set a negative $cp number
     * 'levelup': set change mode to 'level_ap' and set positive $cp number
     *
     * @param string  $pointsType  'construction_points'|'research_points'
     * @param numeric $entityId
     * @param numeric $colonyId
     * @param numeric $cp Construction points to invest
     * @param string  $changeMode 'status'|'progress'
     */
    protected function _invest($pointsType, $colonyId, $entityId, $changeMode='status', $cp=1)
    {
        $this->_validateId($colonyId);
        $this->_validateId($entityId);

        $this->getLogger()->log(
            \Zend\Log\Logger::INFO,
            "$changeMode cp to building $entityId on colony $colonyId"
        );

        $result = false;
        // check if enough action points are available
        if ($pointsType == 'research_points') {
            $availableAP = $this->getService('personell')->getResearchPoints($colonyId);
        } else {
            $availableAP = $this->getService('personell')->getConstructionPoints($colonyId);
        }

        if ($availableAP >= abs($cp)) {
            $table = $this->getTable($this->getColonyEntitiesTableName());
            try {
                # start transaction
                $table->getAdapter()->getDriver()->getConnection()->beginTransaction();

                # save new possession level
                $this->getLogger()->log(\Zend\Log\Logger::INFO, array($colonyId, $entityId));
                $this->getLogger()->log(\Zend\Log\Logger::INFO, $this->getColonyEntitiesTableName());

                $poss = $this->getColonyEntity($colonyId, $entityId);
                $status_before = $poss->getStatusPoints();
                $building = $this->getEntity($entityId);
                if (!$building) {
                    $poss = array(
                        'colony_id' => $colonyId,
                        $this->getEntityIdName() => $entityId,
                        'level' => 0,
                        'status_points' => 0,
                        'ap_spend' => 0
                    );
                } else {
                    $poss = $poss->getArrayCopy();
                }

                if (strtolower($changeMode) == 'progress') {
                    $points = max($poss['ap_spend']+abs($cp), $building->getApForLevelup());
                    $poss['ap_spend'] = $points;
                } else {
                    if ($cp > 0) {
                        $points = max($poss['status_points']+abs($cp), $building->getMaxStatusPoints());
                        $poss['status_points'] = $points;
                    } else {
                        $points = min($poss['status_points']-abs($cp), 0);
                        $poss['status_points'] = $points;
                    }
                }
                
                $result = $table->save($poss);
                $status_after = $poss->getStatusPoints();
                # pay Costs (only if repair -> that means positive action points)
                if (strtolower($changeMode) == 'status' && $cp > 0) {
                    $costs = $this->getEntityCosts($entityId);
                    if (!empty($costs)) {
                        $costs = $costs->getArrayCopy('resource_id');
                        $repairCosts = $costs;
                        foreach ($costs as $resId => $cost) {
                            $repairCosts[$resId]['amount'] = floor($cost['amount']/$tech->getMaxStatusPoints());
                        }
                        $this->getLogger()->log(
                            \Zend\Log\Logger::INFO,
                            'Pay resources for repair: ' . serialize($repairCosts)
                        );
                        $this->getService('resources')->payCosts($repairCosts, $colonyId);
                    }
                }

                $effective_spend_ap = abs($status_before-$status_after);
                if ($effective_spend_ap > 0 ) {
                    # lock action points

                    if ($pointsType == 'research_points') {
                        $this->getService('personell')->lockActionPoints('research', $colonyId, $effective_spend_ap);
                    } else {
                        $this->getService('personell')->lockActionPoints('construction', $colonyId, $effective_spend_ap);
                    }
                }

                # commit transaction
                $table->getAdapter()->getDriver()->getConnection()->commit();
            } catch (Exception $e) {
                $this->getLogger()->log(
                    \Zend\Log\Logger::INFO,
                    'Change technology status for building ' . $entityId . ' on colony ' . $colonyId . ' failed'
                );
                $table->getAdapter()->getDriver()->getConnection()->rollback();
            }
        }

        return (bool) $result;
    }

    public function levelup($colonyId, $entityId)
    {
        # check invested ap
        # check required buildings
        $required_buildings_check = $this->checkRequiredBuildingsByEntityId($entityId, $colonyId);
        # check required resources
        $required_resources_check = $this->checkRequiredResourcesByBuildingId($entityId, $colonyId);
        # reduce resources
        # remove one tech level

        # TODO
    }

    public function leveldown($colonyId, $entityId)
    {
        # check invested ap
        # leveldown

        # TODO
    }
}