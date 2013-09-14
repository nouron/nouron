<?php
namespace Techtree\Service;

class Gateway extends \Nouron\Service\AbstractService
{
    const ADVISOR_ENGINEER_TECHID = 35;
    const ADVISOR_SCIENTIST_TECHID = 36;
    const ADVISOR_FLEETCOMMANDER_TECHID = 89;
    const ADVISOR_DIPLOMAT_TECHID = 90;
    const ADVISOR_CHIEFOFINTELLIGENCE = 94;

    const DEFAULT_ACTIONPOINTS = 5;

    /**
     * get Requirements as array in the form:
     * requirements[$techId][$requiredTechId][$count];
     *
     * array (
     *   1 => array (
     *      2 => 3,
     *   )
     * )
     *
     * @return array
     */
    public function getRequirementsAsArray($where = null, $order = null)
    {
        $rowset = $this->getRequirements($where, $order);
        $this->_requirements = array();
        while ( $rowset->valid() ){
            $req = $rowset->current();
            $t1_id = $req->tech_id;
            $t2_id = $req->required_tech_id;
            $this->_requirements[$t1_id] = array();
            $this->_requirements[$t1_id][$t2_id] =  $req->required_tech_level;
            $rowset->next();
        }

        return $this->_requirements;
    }

    /**
     * @return ResultSet
     */
    public function getTechnologies()
    {
        return $this->getTable('technology')->fetchAll();
    }

    /**
     *
     * @param integer $techId
     * @return \Techtree\Entity\Technology
     */
    public function getTechnology($techId)
    {
        $this->_validateId($techId);
        return $this->getTable('technology')->getEntity($techId);
    }

    /**
     * @return ResultSet
     */
    public function getCosts($where = null, $order = null)
    {
        return $this->getTable('cost')->fetchAll($where = null, $order = null);
    }

    /**
     * Get the costs of the given technology.
     *
     * @param  integer $techId
     * @return ResultSet
     */
    public function getCostsByTechnologyId($techId)
    {
        $this->_validateId($techId);
        return $this->getTable("cost")->fetchAll("tech_id = $techId");
    }

    /**
     *
     * @param string|array $where
     * @param string|array $order
     * @return ResultSet
     */
    public function getRequirements($where = null, $order = null)
    {
        return $this->getTable('requirement')->fetchAll($where, $order);
    }

    /**
     *
     * @param integer $colonyId
     * @return array
     */
    public function getTechtreeByColonyId($colonyId)
    {
        $this->_validateId($colonyId);

        // Technologien-Stammdaten holen
        $techs = $this->getTechnologies();
        $techs = $techs->getArrayCopy('id');

        // Besitz holen
        $poss = $this->getPossessionsByColonyId($colonyId);
        $poss = $poss->getArrayCopy('tech_id');

        $requirements = $this->getRequirementsAsArray();

        // Besitz und Kosten den Stammdaten zuordnen
        foreach ($techs as $id => $t)
        {
            if (isset($poss[$id])) {
                $techs[$id]['level'] = isset($poss[$id]['level']) ? $poss[$id]['level'] : 0;
                $techs[$id]['ap_spend']   = isset($poss[$id]['ap_spend']) ? $poss[$id]['ap_spend'] : 0;
                //@todo: slot
            } else {
                $techs[$id]['level'] = 0;
                $techs[$id]['ap_spend']   = 0;
                //@todo: slot
            }

            $checkReqs = $this->checkRequiredResourcesByTechId($id, $colonyId);
            if ($checkReqs == true) {
                $techs[$id]['status'] = 'available';
            } elseif ($techs[$id]['level'] > 0) {
                $techs[$id]['status'] = 'inactive';
            } else {
                $techs[$id]['status'] = 'not available';
            }
        }

        return $techs;
    }

    /**
     * Abfrage der Voraussetzungen für eine Technologie
     *
     * @param  integer $techId
     * @return ResultSet
     */
    public function getRequirementsByTechnologyId($techId)
    {
        $this->_validateId($techId);
        return $this->getTable('requirement')->fetchAll("tech_id = $techId");
    }

    /**
     * Get the technologies in possession from given colony.
     *
     * @param  integer $colonyId
     * @return ResultSet
     */
    public function getPossessionsByColonyId($colonyId)
    {
        $this->_validateId($colonyId);
        return $this->getTable('possession')->fetchAll("colony_id = $colonyId");
    }

    /**
     *
     * @param  integer $userId
     * @return \Techtree\Model\Possessions
     */
    public function getPossessionsByUserId($userId)
    {
        $this->_validateId($userId);

        $galaxyGateway = new \Galaxy\Service\Gateway();
        $colonies = $galaxyGateway->getColoniesByUserId($userId);

//         if (!$colonies->valid() || !($colonies instanceof \Galaxy\Model\Colonies)) {
//             return new \Techtree\Model\Possessions(array(), $this);
//         }

        if ( $colonies->count() > 1 ) {
            foreach ($colonies as $col) {
                $coloIds[] = $col->id;
            }
            $coloIds = implode($coloIds, ',');
            $possessions = $this->getPossessions("colony_id IN ($coloIds)");
        } else {
            $possessions = $this->getPossessionsByColonyId($colonies->id);
        }

        return $possessions;
    }

    /**
     *
     * @param  integer $techId
     * @param  integer $colonyId
     * @param  integer $ap
     * @return boolean
     */
    private function _addActionPointsForLevelUp($colonyId, $techId, $ap)
    {
        $this->_validateId($colonyId);
        $this->_validateId($techId);

        // check if enough action points are available
        $techTable = $this->getTable('technology');
        $tech = $techTable->getEntity($techId);
        $availableAp = $this->getAvailableActionPoints($tech->type, $colonyId);

        $result = false;
        if ($availableAp >= $ap) {
            $this->getLogger()->log(
                \Zend\Log\Logger::INFO,
                "add $ap action points to technology $techId on colony $colonyId"
            );
            $table = $this->getTable('possession');
            try {
                # start transaction
                $table->getAdapter()->getDriver()->getConnection()->beginTransaction();

                $table = $this->getTable('possession');
                $poss = $table->getEntity(array(
                    'colony_id' => $colonyId,
                    'tech_id' => $techId,
                ));
                if (empty($poss)) {
                    $poss = array(
                        'colony_id' => $colonyId,
                        'tech_id' => $techId,
                        'ap_spend' => 0
                    );
                } else {
                    $poss = $poss->getArrayCopy();
                }
                $poss['ap_spend'] += (int) $ap;
                $result = $table->save($poss);

                # lock action points
                $this->_lockActionPoints($tech->type, $colonyId, $ap);
                $table->getAdapter()->getDriver()->getConnection()->commit();

            } catch(Exception $e) {
                $this->getLogger()->log(
                    \Zend\Log\Logger::ERROR,
                    "levelup failed for technology $techId on colony $colonyId"
                );

                $table->getAdapter()->getDriver()->getConnection()->rollback();
            }
        }

        return (bool) $result;
    }

    /**
     * Remove action points as a prepartion for leveldown.
     * ATTENTION: this does NOT reduce the amount of spended ap for levelup.
     *
     * @param  integer $techId
     * @param  integer $colonyId
     * @param  integer $ap
     * @return boolean
     */
    private function _addActionPointsForLevelDown($colonyId, $techId, $ap)
    {
        $this->getLogger()->log(
            \Zend\Log\Logger::INFO,
            "remove $ap action points to technology $techId on colony $colonyId"
        );

        $ap = abs((int) $ap);
        $result = $this->_changeStatus($colonyId, $techId, -$ap);

        return (bool) $result;
    }

    /**
     *
     * @param  integer $techId
     * @param  integer $colonyId
     * @param  integer $ap
     * @return boolean
     */
    private function _addActionPointsForRepair($colonyId, $techId, $ap)
    {
        $this->getLogger()->log(\Zend\Log\Logger::INFO, "repair technology $techId on
                           colony $colonyId with $ap action points");

        $ap = abs((int) $ap);
        $costs = $this->getCostsByTechnologyId($techId);
        $result = $this->_changeStatus($colonyId, $techId, $ap, $costs);

        return (bool) $result;
    }

    /**
     *
     * @param  integer $colonyId
     * @param  integer $techId
     * @param  integer $ap
     * @return boolean
     */
    private function _changeStatus($colonyId, $techId, $ap)
    {
        $this->_validateId($colonyId);
        $this->_validateId($techId);

        $result = false;
        // check if enough action points are available
        $techTable = $this->getTable('technology');
        $tech = $techTable->getEntity($techId);
        $availableAP = $this->getAvailableActionPoints($tech->type, $colonyId);

        if ($availableAP >= $ap) {
            $table = $this->getTable('possession');
            try {
                # start transaction
                $table->getAdapter()->getDriver()->getConnection()->beginTransaction();

                # save new possession level
                $poss = $table->getEntity(array(
                    'colony_id' => $colonyId,
                    'tech_id' => $techId,
                ));
                $poss->status_points += (int) $ap;
                $result = $table->save($poss);

                # pay Costs (only if repair -> that means positive action points)
                if ($ap > 0) {
                    $costs = $this->getCostsByTechnologyId($techId);
                    if (!empty($costs)) {
                        $costs = $costs->getArrayCopy('resource_id');
                        $repairCosts = $costs;
                        foreach ($costs as $resId => $cost) {
                            $repairCosts[$resId]['amount'] = floor($cost['amount']/$tech->max_status_points);
                        }
                        $this->getLogger()->log(
                            \Zend\Log\Logger::INFO,
                            'Pay resources for repair: ' . serialize($repairCosts)
                        );
                        $resourcesGw = $this->getService('resources');
                        $resourcesGw->payCosts($repairCosts, $colonyId);
                    }
                }

                # lock action points
                $this->_lockActionPoints($tech->type, $colonyId, $ap);

                # commit transaction
                $table->getAdapter()->getDriver()->getConnection()->commit();
            } catch (Exception $e) {
                $this->getLogger()->log(
                    \Zend\Log\Logger::INFO,
                    'Change technology status for technology ' . $techId . ' on colony ' . $colonyId . ' failed'
                );
                $table->getAdapter()->getDriver()->getConnection()->rollback();
            }
        }

        return (bool) $result;
    }

    /**
     * lock used action points for current tick
     *
     * @param string $type
     * @param integer $colonyId
     * @param integer $ap
     * @return boolean
     */
    private function _lockActionPoints($type, $colonyId, $ap)
    {
        $this->_validateId($colonyId);

        $tick = $this->getTick();
        $table = $this->getTable('log_actionpoints');
        $techId = $this->_getPersonellIdByTechType($type);
        $entity = $table->getEntity(array(
            'tick' => $tick,
            'colony_id' => $colonyId,
            'personell_tech_id' => $techId
        ));

        if (empty($entity)) {
            $entity = array(
                'tick' => $tick,
                'colony_id' => $colonyId,
                'personell_tech_id' => $techId,
                'spend_ap' => 0
            );
        } else {
            $entity = $entity->getArrayCopy();
        }

        $entity['spend_ap'] += abs((int) $ap);

        $this->getLogger()->log(
            \Zend\Log\Logger::INFO,
            serialize($entity)
        );

        return $table->save($entity);

    }

    /**
     *
     * @param  integer $colonyId
     * @param  integer $techId
     * @param  string $order
     * @return boolean
     */
    public function order($colonyId, $techId, $order, $ap = null)
    {
        $this->_validateId($colonyId);
        $this->_validateId($techId);

        $ap = empty($ap) ? 1 : $ap;

        switch (strtolower($order)) {
            case 'levelup':
                $result = $this->_performLevelUp($colonyId, $techId);
                break;
            case 'leveldown':
                $result = $this->_performLevelDown($colonyId, $techId);
                break;
            case 'repair':
                $result = $this->_addActionPointsForRepair($colonyId, $techId, $ap);
                break;
            case 'add':
                $result = $this->_addActionPointsForLevelUp($colonyId, $techId, $ap);
                break;
            case 'remove':
                $result = $this->_addActionPointsForLevelDown($colonyId, $techId, $ap);
                break;
            default:
                $result = false;
                break;
        }

        return $result;
    }

    /**
     * Immidiatly change the level of the technology level after checking
     * requirements.
     *
     * Checks if all requirements for technology level up are fullfilled
     * (technology dependencies, resources,..). If the check is true  1 technology
     * level is added, otherwise an exception with the message of failed requirement
     * is thrown.
     *
     * what this function does:
     *      - check if maximum is reached
     *      - check technology requirements for levelup
     *      - check resource requirements for levelup
     *      - if all checks are succesfull:
     *          - pay the costs of that technology level up
     *          - add a level to the technology
     *          - clear invested action points
     *      - else:
     *          - throws exception
     *      - throws exception if an error occurs
     *
     * @param  integer  $techId     Which technology...
     * @param  integer  $colonyId   ... on which colony
     * @return boolean
     * @throws \Techtree\Service\Exception  if invalid parameter(s)
     * @throws \Techtree\Service\Exception  if one of the checks was negative
     */
    private function _performLevelUp($colonyId, $techId)
    {
        $this->_validateId($colonyId);
        $this->_validateId($techId);

        $this->getLogger()->log(
            \Zend\Log\Logger::INFO,
            "add one level to technology $techId on colony $colonyId"
        );

        $tech = $this->getTechnology($techId);
        if ( is_null($tech) ) {
            throw new \Techtree\Service\Exception('exception_unknowntechnology_id');
        }

        // check if maxlevel is reached
        $poss = $this->getPossessionByTechnologyId($techId, $colonyId);
        $possessLevel = $poss->level;
        $maxLevel  = (int) $tech->max_level;

        if ( $maxLevel > 0 && $possessLevel >= $maxLevel ) {
            throw new \Techtree\Service\Exception('exception_maximumreached '.$maxLevel.' '.$possessLevel);
        }

        // check techtree requirements
        if ( !($this->checkRequiredTechsByTechId($techId, $colonyId)) ) {
            throw new \Techtree\Service\Exception('exception_failed_requirements');
        }

        // check if player colony has enough resources
        if ( !($this->checkRequiredResourcesByTechId($techId, $colonyId)) ) {
            throw new \Techtree\Service\Exception('exception_NotEnoughResources');
        }
        $totalAP = $this->getTotalActionPoints($tech->type, $colonyId);
        $availableAP = $this->getAvailableActionPoints($tech->type, $colonyId);

        if ($tech->ap_for_levelup == 0 or ($poss->ap_spend >= $tech->ap_for_levelup)) {
            $table = $this->getTable('possession');
            try {
                # No levelup without paying resources! So we have to use a
                # transaction and rollback all changes in case of an error

                # start transaction
                $table->getAdapter()->getDriver()->getConnection()->beginTransaction();
                $poss->level += 1;
                $poss->ap_spend = 0; # reset action points
                $poss->status_points = $tech->max_status_points; # reset status points
                $result = $table->save($poss);

                $costs = $this->getCostsByTechnologyId($techId);
                $resourcesGw = $this->getService('resources');

                $resourcesGw->payCosts($costs, $colonyId);

                # commit transaction
                $table->getAdapter()->getDriver()->getConnection()->commit();
            } catch (Exception $e) {
                # rollback transaction
                $table->getAdapter()->getDriver()->getConnection()->rollback();
                $this->getLogger()->log(
                    \Zend\Log\Logger::ERROR,
                    "failed levelup for $techId on colony $colonyId"
                );
            }
        } else {
            throw new \Techtree\Service\Exception('exception_NotEnoughInvestedActionPoints');
        }

        return (bool) $result;
    }

    /**
     * Check requirements and reduce technology level by 1.
     *
     * what this function does:
     *      - check if minimum is reached
     *      - if check is successfull:
     *          - reduce 1 level
     *      - else:
     *          - throws exception
     *      - throws exception if an error occurs
     *
     * @param  integer $colonyId
     * @param  integer $techId
     * @throws \Techtree\Service\Exception
     * @return boolean
     */
    private function _performLevelDown($colonyId, $techId)
    {
        $this->_validateId($colonyId);
        $this->_validateId($techId);

        $this->getLogger()->log(
            \Zend\Log\Logger::INFO,
            "remove one level from technology $techId on colony $colonyId"
        );

        $tech = $this->getTechnology($techId);
        if ( is_null($tech) ) {
            throw new \Techtree\Service\Exception('exception_Unknowtechnology_id');
        }

        // check if maxlevel is reached
        $poss = $this->getPossessionByTechnologyId($techId, $colonyId);
        $possessLevel = $poss->level;

        if ( $possessLevel <= 0 ) {
            throw new \Techtree\Service\Exception(
                'exception_MinimumReached ' . $maxLevel . ' ' . $possessLevel
            );
        }

        $totalAP = $this->getTotalActionPoints($tech->type, $colonyId);
        $availableAP = $this->getAvailableActionPoints($tech->type, $colonyId);

        if ($poss->status_points <= 0 or $tech->type == 'advisor') {
            $table = $this->getTable('possession');
            try {
                # start transaction
                $table->getAdapter()->getDriver()->getConnection()->beginTransaction();
                $poss->level -= 1;
                $poss->ap_spend = 0; # reset action points
                $poss->status_points = $tech->max_status_points; # reset status points
                $result = $table->save($poss);
                # commit transaction
                $table->getAdapter()->getDriver()->getConnection()->commit();
            } catch (Exception $e) {
                # rollback transaction
                $table->getAdapter()->getDriver()->getConnection()->rollback();
                $this->getLogger()->log(
                    \Zend\Log\Logger::ERROR,
                    "failed leveldown for $techId on colony $colonyId"
                );
            }
        } else {
            throw new \Techtree\Service\Exception('exception_NotEnoughInvestedActionPoints');
        }

        return (bool) $result;
    }

    /**
     * Check if Requirements for a technology on a colony are fullfilled.
     *
     * @param  integer  $techId
     * @param  integer  $colonyId
     * @return boolean
     * @throws \Techtree\Model\Exception if invalid parameter(s)
     */
    public function checkRequiredTechsByTechId($techId, $colonyId)
    {
        $this->_validateId($techId);
        $this->_validateId($colonyId);

        $poss  = $this->getPossessionsByColonyId($colonyId)->getArrayCopy('tech_id');
        $rqrmnts = $this->getRequirementsByTechnologyId($techId);

        // compare possession with requirements:
        foreach ($rqrmnts as $rq) {
            $id = $rq->required_tech_id;
            if ( !isset($poss[$id]) || $rq->required_tech_level > $poss[$id]['level']) {
                // if not enough techs in possess return false
                return false;
            }
        }

        return true;
    }

    /**
     * Check if there are enough resources for a technolgy on a colony.
     *
     * @param  integer $techId
     * @param  integer $colonyId
     * @return boolean
     * @throws \Techtree\Model\Exception if invalid parameter(s)
     */
    public function checkRequiredResourcesByTechId($techId, $colonyId)
    {
        $this->_validateId($techId);
        $this->_validateId($colonyId);

        // get costs of technology:
        $costs = $this->getCostsByTechnologyId($techId);
        return $this->getService('resources')->check($costs, $colonyId);
    }

    /**
     *
     * @param  integer $techId
     * @param  integer $colonyId
     * @return mixed|NULL
     */
    public function getPossessionByTechnologyId($techId, $colonyId)
    {
        $this->_validateId($techId);
        $this->_validateId($colonyId);

        $dbTable = $this->getTable('possession');
        $rowset = $dbTable->fetchAll("tech_id = $techId AND colony_id = $colonyId");
        if ($rowset->valid() and $rowset->count() > 0) {
            return $rowset->current();
        }
        return null;
    }

    /**
     * Get the level of a technology on a colony.
     *
     * @param  integer $techId
     * @param  integer $colonyId
     * @return integer
     * @throws \Techtree\Model\Exception if invalid parameter(s)
     */
    public function getLevelByTechnologyId($techId, $colonyId)
    {
        $poss =  $this->getPossessionByTechnologyId($techId, $colonyId);
        if ($poss) {
            return $poss->level;
        } else {
            return 0;
        }
    }

    /**
     *
     * @param  string $type
     * @return integer
     */
    private function _getPersonellIdByTechType($type)
    {
        switch (strtolower($type)) {
            case 'building':  $techId = self::ADVISOR_ENGINEER_TECHID; break;
            case 'research':  $techId = self::ADVISOR_SCIENTIST_TECHID; break;
            case 'fleet':     $techId = self::ADVISOR_FLEETCOMMANDER_TECHID; break;
            case 'diplomacy': $techId = self::ADVISOR_DIPLOMAT_TECHID; break;
            //case 'military': $techId = self::..;break;
            case 'spy':       $techId = self::ADVISOR_CHIEFOFINTELLIGENCE; break;
            default:  $techId = self::ADVISOR_ENGINEER_TECHID; break;
        }
        return $techId;
    }

    /**
     *
     * @param  string  $type
     * @param  integer $colonyId
     * @return number
     */
    public function getTotalActionPoints($type, $colonyId)
    {
        $this->_validateId($colonyId);
        $personellTechId = $this->_getPersonellIdByTechType($type);
        $level = $this->getLevelByTechnologyId($personellTechId, $colonyId);
        return ( $level * self::DEFAULT_ACTIONPOINTS + self::DEFAULT_ACTIONPOINTS );
    }

    /**
     * get available action points for current tick
     *
     * @param  string  $type
     * @param  integer $colonyId
     * @return number
     */
    public function getAvailableActionPoints($type, $colonyId)
    {
        $this->_validateId($colonyId);

        $totalAP = $this->getTotalActionPoints($type, $colonyId);
        $techId  = $this->_getPersonellIdByTechType($type);

        $data = array(
                    'tick' => $this->tick,
                    'colony_id' => $colonyId,
                    'personell_tech_id' => $techId
                );

        $loggedActionpoints = $this->getTable('log_actionpoints')
                                   ->getEntity($data);

        if ( empty($loggedActionpoints) ) {
            $loggedActionpoints = new \Techtree\Entity\ActionPoint($data);
        }

        $usedAP = $loggedActionpoints->spend_ap;

        return ( $totalAP - $usedAP);
    }

    /**
     *
     * @param  integer $userId
     * @return array
     */
    public function countTechsByType($userId)
    {
        $result = array(
            'buildings' => 0,
            'research' => 0,
            'ships' => 0,
            'advisors' => 0
        );

        $possessions = $this->getPossessionsByUserId($userId);

        while ($possessions->valid()) {
            switch ($possessions->type) {
                case 'building': $result['buildings'] += $possessions->level; break;
                case 'research': $result['research']  += $possessions->level; break;
                case 'ships':    $result['ships']     += $possessions->level; break;
                case 'advisor':  $result['advisors']  += $possessions->level; break;
                default: break;
            }
            $possessions->next();
        }

        return $result;

    }

    /**
     *
     * @param  integer $userId
     * @return array
     */
    public function countTechsByPurpose($userId)
    {
        $result = array(
            'civil' => 0,
            'industry' => 0,
            'economy' => 0,
            'politics' => 0,
            'military' => 0
        );

        $possessions = $this->getPossessionsByUserId($userId);

        while ($possessions->valid()) {
            switch ($possessions->purpose) {
                case 'civil':     $result['civil']    += $possessions->level; break;
                case 'industry':  $result['industry'] += $possessions->level; break;
                case 'economy':   $result['economy']  += $possessions->level; break;
                case 'politics':  $result['politics'] += $possessions->level; break;
                case 'military':  $result['military'] += $possessions->level; break;
                default: break;
            }
            $possessions->next();
        }

        return $result;
    }

//     /**
//      *
//      * @param integer $userId
//      * @param integer $sinceTick
//      */
//     public function getProcessedOrders($userId, $sinceTick = null)
//     {
//         $this->_validateId($userId);

//         $lastTick = $this->tick - 1;

//         if ( !is_numeric($sinceTick) ) {
//             $sinceTick = $lastTick;
//         }

//         $galaxyGateway = new \Galaxy\Model\Gateway();
//         $colonies = $galaxyGateway->getColoniesByUserId($userId);

//         if ($colonies->count() == 1) {
//             $whereColo = "colony_id = $colonies->id";
//         } else {
//             while ($colonies->valid()) {
//                 $colonyIds[] = $colonies->id;
//                 $colonies->next();
//             }
//             $colonyIds = implode($colonyIds, ',');
//             $whereColo = "colony_id IN ($colonyIds)";
//         }

//         return $this->getOrders("$whereColo AND tick >= $sinceTick AND tick <= $lastTick AND is_final_step = 1 AND was_processed");
//     }

//     /**
//      * Find all processed orders and notify innn and eventDispatcher about them.
//      * Set is_notified to true to avoid a double notification.
//      *
//      * - set innn events
//      * - set event dispatcher events
//      *
//      * @param integer $userId
//      */
//     public function notifyAboutProcessedOrders($userId)
//     {
//         $this->_validateId($userId);


//         $innnGw = new \Innn\Model\Gateway();
//         $orders = $this->getProcessedOrders($userId);

//         $tick = $this->tick;
// //         $cache = $this->getServiceLocator()->get('cache');

//         while ( $orders->valid() ) {

//             if ( $orders->is_notified == 0 ) {

//                 $order = $orders->current();

//                 if ( $order->order == 'sub' ) {
//                     $event = 'techtree.level_down_finished';
//                 } else {
//                     $event = 'techtree.level_up_finished';
//                 }

//                 // set innn event:
//                 $data = array(
//                     'user_id' => $userId,
//                     'tick' => $tick,
//                     'event' => $event,
//                     'parameters' => serialize(array('tech_id' => $order->tech_id, 'colony_id' => $order->colony_id))
//                 );

//                 $innnEvent = $innnGw->createEvent($data);
//                 $innnEvent->save();

//                 // notify event dispatcher:
//                 $event = new sfEvent($this, $event );
//                 $dispatcher = $this->getServiceLocator()->get('eventDispatcher');
//                 $dispatcher->notify($event);

//                 $order->is_notified = 1;
//                 $order->save();

//                 //$cache->clean(Zend_Cache::CLEANING_MODE_MATCHING_TAG, array('techtree','orders'));
//             }
//             $orders->next();
//         }
//     }

    /**
     *
     */
    public function setGridPosition($techId, $row, $column)
    {
        $tech = $this->getTechnology($techId);
        $tech->row = $row;
        $tech->column = $column;
        return $this->getTable('technology')->save($tech);
    }
}