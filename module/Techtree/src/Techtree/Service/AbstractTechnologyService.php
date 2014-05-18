<?php
namespace Techtree\Service;

use Nouron\Service\AbstractService;

abstract class AbstractTechnologyService extends AbstractService implements TechnologyServiceInterface
{

    /**
     * @return string
     */
    abstract protected function getEntitiesTableName();

    /**
     * @return string
     */
    abstract protected function getColonyEntitiesTableName();

    /**
     * @return string
     */
    abstract protected function getEntityCostsTableName();

    /**
     * @return string
     */
    abstract protected function getEntityIdName();

    /**
     * @see \Techtree\Service\TechnologyServiceInterface::getEntity()
     * @param numeric $entityId
     * @return ResultSet
     */
    public function getEntity($entityId)
    {
        $this->_validateId($entityId);
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
     * @throws \Techtree\Service\Exception if invalid parameter(s)
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
            $this->getLogger()->log(
                \Zend\Log\Logger::ERR,
                array('required buildings check failed', $requiredBuildingId, $colonyBuildings)
            );
            return False;
        } else {
            $requiredBuilding = $colonyBuildings[$requiredBuildingId];
            return ($requiredBuilding['level'] >= $entity['required_building_level']);
        }
    }

    /**
     *
     *
     * @param numeric $entityId
     * @param numeric $colonyId
     * @return boolean
     * @throws \Techtree\Service\Exception if invalid parameter(s)
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
     * @throws \Techtree\Service\Exception if invalid parameter(s)
     */
    public function checkRequiredResourcesByEntityId($colonyId, $entityId)
    {
        $this->_validateId($entityId);
        $this->_validateId($colonyId);

        $costs = $this->getEntityCosts($entityId);
        return true;#$this->getService('resources')->check($costs, $colonyId);
    }

    /**
     * Check if required action points are fullfilled
     *
     * @param numeric $entityId
     * @param numeric $colonyId
     * @return boolean  True if all required action points were spend else False
     * @throws \Techtree\Service\Exception if invalid parameter(s)
     */
    public function checkRequiredActionPoints($colonyId, $entityId)
    {
        $this->_validateId($entityId);
        $this->_validateId($colonyId);

        if ($this->getEntityIdName() == 'personell_id') {
            return true;
        } else {
            $entity = $this->getEntity($entityId);
            $colonyEntity = $this->getColonyEntity($colonyId, $entityId);
            if ($colonyEntity->getApSpend() >= $entity->getApForLevelup()) {
                return true;
            } else {
                return false;
            }
        }
    }

    /**
     *
     * @param numeric $entityId
     * @param numeric $colonyId
     * @return boolean
     * @throws \Techtree\Service\Exception if invalid parameter(s)
     */
    public function checkLevelUpLimit($colonyId, $entityId)
    {
        $this->_validateId($entityId);
        $this->_validateId($colonyId);

        if ($this->getEntityIdName() == 'building_id') {
            $entity = $this->getEntity($entityId);
            $colonyEntity = $this->getColonyEntity($colonyId, $entityId);
            if ($entity->getMaxLevel() >0 && $colonyEntity->getLevel() >= $entity->getMaxLevel()) {
                return false;
            } else {
                return true;
            }
        } else {
            return true;
        }
    }

    /**
     *
     * @param numeric $entityId
     * @param numeric $colonyId
     * @return boolean
     * @throws \Techtree\Service\Exception if invalid parameter(s)
     */
    public function checkLevelDownLimit($colonyId, $entityId)
    {
        $this->_validateId($entityId);
        $this->_validateId($colonyId);

        $entity = $this->getEntity($entityId);
        $colonyEntity = $this->getColonyEntity($colonyId, $entityId);
        if ($colonyEntity->getLevel() <= 0) {
            return false;
        } else {
            return true;
        }
    }

    /**
     *
     * 'repair' : set change mode to 'status' and set a positive $points number
     * 'destroy': set change mode to 'status' and set a negative $points number
     * 'levelup': set change mode to 'progress' and set positive $points number
     *
     * @param string  $pointsType  'construction_points'|'research_points'
     * @param numeric $entityId
     * @param numeric $colonyId
     * @param integer $points Points to invest
     * @param string  $changeMode 'status'|'progress'
     * @return boolean
     */
    protected function _invest($pointsType, $colonyId, $entityId, $changeMode='add', $points=1)
    {
        $this->_validateId($colonyId);
        $this->_validateId($entityId);

        $this->getLogger()->log(
            \Zend\Log\Logger::INFO,
            "$changeMode $points $pointsType to technology $entityId on colony $colonyId"
        );

        $result = false;
        // check if enough action points are available
        if ($pointsType == 'research_points') {
            $availableAP = $this->getService('personell')->getResearchPoints($colonyId);
        } else {
            $availableAP = $this->getService('personell')->getConstructionPoints($colonyId);
        }

        if ($availableAP >= abs($points)) {
            $table = $this->getTable($this->getColonyEntitiesTableName());
            try {
                # start transaction
                $table->getAdapter()->getDriver()->getConnection()->beginTransaction();

                # save new possession level
                $poss = $this->getColonyEntity($colonyId, $entityId);
                $status_before = $poss->getStatusPoints();
                $entity = $this->getEntity($entityId);
                if (!$entity) {
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

                if (strtolower($changeMode) == 'add') {
                    $points = min($poss['ap_spend']+abs($points), $entity->getApForLevelup());
                    $poss['ap_spend'] = $points;
                } else if (strtolower($changeMode) == 'repair') {
                    $points = min($poss['status_points']+abs($points), $entity->getMaxStatusPoints());
                    $poss['status_points'] = $points;
                } else if (strtolower($changeMode) == 'remove') {
                    $points = max($poss['status_points']-abs($points), 0);
                    $poss['status_points'] = $points;
                } else {
                    throw new Exception('Invalid change mode.');
                }

                $result = $table->save($poss);
                $status_after = $poss['status_points'];
                # pay Costs (only if repair -> that means positive action points)
                if (strtolower($changeMode) == 'repair') {
                    $costs = $this->getEntityCosts($entityId);
                    if (!empty($costs)) {
                        $costs = $costs->getArrayCopy('resource_id');
                        $repairCosts = $costs;
                        foreach ($costs as $resId => $cost) {
                            $repairCosts[$resId]['amount'] = floor($cost['amount']/$entity->getMaxStatusPoints());
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

    /**
     * levelup an colony entity (like building, research, ship) if requirements are fullfilled
     *
     * @param numeric $colonyId
     * @param numeric $entityId
     */
    public function levelup($colonyId, $entityId)
    {
        $result = null;

        # check required buildings
        $required_buildings_check     = $this->checkRequiredBuildingsByEntityId($colonyId, $entityId);
        # check required resources
        $required_resources_check     = $this->checkRequiredResourcesByEntityId($colonyId, $entityId);
#        # check required researches
#        $required_researches_check    = $this->checkRequiredResearchesByEntityId($colonyId, $entityId);
#        # check invested ap
#        $required_action_points_check = $this->checkRequiredActionPoints($colonyId, $entityId);
#        # check if maximum ist not reached yet
#        $levelup_limit_check          = $this->checkLevelUpLimit($colonyId, $entityId);
#
#        print("\n");
#        var_dump($required_buildings_check);
#        var_dump($required_resources_check);
#        var_dump($required_researches_check);
#        var_dump($required_action_points_check);
#        var_dump($levelup_limit_check);
#        print("\n");
#
#        if ($required_buildings_check && $required_resources_check &&
#            $required_researches_check && $required_action_points_check &&
#            $levelup_limit_check)
#        {
#            $table = $this->getTable($this->getColonyEntitiesTableName());
#            try {
#                # start transaction
#                $table->getAdapter()->getDriver()->getConnection()->beginTransaction();
#
#                # reduce resources
#                $costs = $this->getEntityCosts($entityId);
#                $this->getService('resources')->payCosts($costs, $colonyId);
#
#                # add one tech level
#                $colonyEntity = $this->getColonyEntity($colonyId, $entityId);
#                $entity       = $this->getEntity($entityId);
#                $poss = array(
#                    'colony_id' => $colonyId,
#                    $this->getEntityIdName() => $entityId,
#                    'status_points' => $entity->getMaxStatusPoints(), // reset to max
#                    'level'    => ($colonyEntity ? $colonyEntity->getLevel() + 1 : 1),
#                );
#
#                if ($this->getEntityIdName() != 'personell_id') {
#                    $poss['ap_spend'] = 0; // reset to none (ignored when entity type = personnell)
#                }
#
#                $result = $table->save($poss);
#
#                # commit transaction
#                $table->getAdapter()->getDriver()->getConnection()->commit();
#            } catch (Exception $e) {
#                $this->getLogger()->log(
#                    \Zend\Log\Logger::INFO,
#                    'Add ' . $entityId . ' on colony ' . $colonyId . ' failed'
#                );
#                # rollback transaction
#                $table->getAdapter()->getDriver()->getConnection()->rollback();
#            }
#        } else {
#            $this->getLogger()->log(\Zend\Log\Logger::ERR, 'at least one levelup requirements check failed');
#            $this->getLogger()->log(\Zend\Log\Logger::INFO, array(
#                $required_buildings_check,
#                $required_resources_check,
#                $required_researches_check,
#                $required_action_points_check,
#                $levelup_limit_check)
#            );
#        }
#
#        return (bool) $result;
        return true;
    }

    /**
     * leveldown an colony entity (like building, research, ship) if requirements are fullfilled
     *
     * @param numeric $colonyId
     * @param numeric $entityId
     */
    public function leveldown($colonyId, $entityId)
    {
        $result = null;

        # check required buildings
        $required_buildings_check     = $this->checkRequiredBuildingsByEntityId($colonyId, $entityId);
        # check required resources
        $required_resources_check     = $this->checkRequiredResourcesByEntityId($colonyId, $entityId);
        # check required researches
        $required_researches_check    = $this->checkRequiredResearchesByEntityId($colonyId, $entityId);
        # check invested ap
        $required_action_points_check = $this->checkRequiredActionPoints($colonyId, $entityId);
        # check if minimum
        $leveldown_limit_check        = $this->checkLevelDownLimit($colonyId, $entityId);

        #print("\n");
        #var_dump($required_buildings_check);
        #var_dump($required_resources_check);
        #var_dump($required_researches_check);
        #var_dump($required_action_points_check);
        #var_dump($leveldown_limit_check);
        #print("\n");


        if ($required_buildings_check && $required_resources_check &&
            $required_researches_check && $required_action_points_check &&
            $leveldown_limit_check)
        {

            $table = $this->getTable($this->getColonyEntitiesTableName());
            try {
                # start transaction
                $table->getAdapter()->getDriver()->getConnection()->beginTransaction();

                # reduce resources
                $costs = $this->getEntityCosts($entityId);
                $this->getService('resources')->payCosts($costs, $colonyId);

                # remove one tech level
                $colonyEntity = $this->getColonyEntity($colonyId, $entityId);
                $entity       = $this->getEntity($entityId);
                $poss = array(
                    'colony_id' => $colonyId,
                    $this->getEntityIdName() => $entityId,
                    'status_points' => $entity->getMaxStatusPoints(), // reset to max
                    'level'    => $colonyEntity->getLevel() - 1
                );
                if ($this->getEntityIdName() != 'personell_id') {
                    $poss['ap_spend'] = 0; // reset to none (ignored when entity type = personnell)
                }
                $result = $table->save($poss);

                # commit transaction
                $table->getAdapter()->getDriver()->getConnection()->commit();
            } catch (Exception $e) {
                $this->getLogger()->log(
                    \Zend\Log\Logger::INFO,
                    'Remove ' . $entityId . ' on colony ' . $colonyId . ' failed'
                );
                $table->getAdapter()->getDriver()->getConnection()->rollback();
            }

        } else {
            $this->getLogger()->log(\Zend\Log\Logger::INFO, 'at least one leveldown requirements check failed');
        }

        return (bool) $result;
    }

}
