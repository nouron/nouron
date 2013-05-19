<?php
namespace Techtree\Service;

class Gateway extends \Nouron\Service\Gateway
{
    const ADVISOR_ENGINEER_TECHID = 35;
    const ADVISOR_SCIENTIST_TECHID = 36;
    const ADVISOR_FLEETCOMMANDER_TECHID = 89;
    const ADVISOR_DIPLOMAT_TECHID = 90;
    const ADVISOR_CHIEFOFINTELLIGENCE = 94;

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
    public function getRequirementsAsArray()
    {
        $rowset = $this->getRequirements();
        $this->_requirements = array();
        while ( $rowset->valid() ){
            $req = $rowset->current();
            $t1_id = $req->tech_id;
            $t2_id = $req->required_tech_id;
            $this->_requirements[$t1_id] = array();
            $this->_requirements[$t1_id][$t2_id] =  $req->required_tech_count;
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
     * @param numeric $techId
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
    public function getCosts()
    {
        return $this->getTable('cost')->fetchAll();
    }

    /**
     * @return ResultSet
     */
    public function getRequirements()
    {
        return $this->getTable('requirement')->fetchAll();
    }

    /**
     *
     * @param numeric $colonyId
     */
    public function getTechtreeByColonyId($colonyId)
    {
        $this->_validateId($colonyId);

        // Technologien-Stammdaten holen
        $techs = $this->getTechnologies()->toArray('id');

        // Besitz holen
        $poss = $this->getPossessionsByColonyId($colonyId);
        $poss = $poss->toArray('tech_id');

        $requirements = $this->getRequirements()->toArray(array('tech_id','required_tech_id'));

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
            } elseif ($techs[$id]['count'] > 0) {
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
     * @param int $techId
     * @return ResultSet
     */
    public function getRequirementsByTechnologyId($techId)
    {
        $this->_validateId($techId);
        return $this->getTable('requirement')->fetchAll("tech_id = $techId");
    }

    /**
     * Get the costs of the given technology.
     *
     * @param  integer $techId
     * @return Costs
     */
    public function getCostsByTechnologyId($techId)
    {
        $this->_validateId($techId);
        return $this->getTable("Cost")->fetchAll("tech_id = $techId");
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
     * @param  numeric $userId
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
     * @param  numeric $colonyId
     * @param  numeric $techId
     * @param  string $order
     * @return boolean
     */
    public function order($colonyId, $techId, $order, $ap)
    {
        $this->_validateId($techId);
        $this->_validateId($colonyId);

        switch ($order) {
            case 'add':    return $this->technologyLevelUp($techId, $colonyId);
                           break;
            case 'remove': return $this->technologyLevelDown($techId, $colonyId);
                           break;
            case 'repair': return $this->technologyLevelRepair($techId, $colonyId);
                           break;
            case 'cancel': return $this->cancelOrders($techId, $colonyId);
                           break;
            default:       // TODO
                           break;
        }

    }

    /**
     * Add an order to raise/lower/repair the technology level within the following ticks.
     *
     * Checks if all requirements for technology level up are fullfilled
     * (technology dependencies, resources,..). If the check is true  1 technology
     * level is added, otherwise an exception with the message of failed requirement
     * is thrown.
     *
     * what this function does:
     *      - check if order already exists
     *      - check if maximum is reached
     *      - check technology requirements for levelup
     *      - check resource requirements for levelup
     *      - if all checks are succesfull:
     *          - pay the costs of that technology level up
     *          - if tech is an advisor:
     *              - add a level immediatly
     *          - else:
     *              - store an order to add 1 level
     *      - else:
     *          - throws exception
     *      - throws exception if an error occurs
     *
     * what this function NOT does:
     *      - add a level immediatly for a technology that is not an advisor
     *
     * @param  integer  $techId     Which technology...
     * @param  integer  $colonyId   ... on which colony
     * @return boolean
     * @throws \Techtree\Service\Exception  if invalid parameter(s)
     * @throws \Techtree\Service\Exception  if one of the checks was negative
     */
    private function technologyLevelUp($techId, $colonyId)
    {
        $this->logger->log(\Zend\Log\Logger::INFO, "add one level to technology $techId on colony $colonyId");

        $tech = $this->getTechnology($techId);
        if ( is_null($tech) ) {
            throw new \Techtree\Service\Exception('exception_Unknowtechnology_id');
        }

        // check if maxlevel is reached
        $poss = $this->getPossessionByTechnologyId($techId, $colonyId);
        $possessLevel = $poss->level;
        $maxLevel  = $tech->max_level;

        if ( !is_null($maxLevel) && $possessLevel >= $maxLevel ) {
            throw new \Techtree\Service\Exception('exception_MaximumReached '.$maxLevel.' '.$possessLevel);
        }

        // check techtree requirements
        if ( ! ($this->checkRequiredTechsByTechId($techId, $colonyId)) ) {
            throw new \Techtree\Model\Exception('exception_FailRequirements');
        }

        // check if player colony has enough resources
        if ( ! ($this->checkRequiredResourcesByTechId($techId, $colonyId)) ) {
            throw new \Techtree\Service\Exception('exception_NotEnoughResources');
        }
        $totalAP = $this->getTotalActionPoints($tech->type, $colonyId);
        $availableAP = $this->getAvailableActionPoints($tech->type, $colonyId);

        if ($tech->ap_for_levelup > 0 and $availableAP > 0)
        {
            $order = $this->getOrderByTechnologyId($techId , $colonyId);
            $apOrdered = ($order) ? $order->ap_ordered : 0;
            $apSpendBefore = ($poss) ? $poss->ap_spend : 0;

            if ($apSpendBefore + $apOrdered + 1 >= $tech->ap_for_levelup) {
                // pay Costs when levelup is ready:
                $costs = $this->getCostsByTechnologyId($techId);
                $this->getGateway('resources')->payCosts($costs, $colonyId);
            }

            $order = array(
                'tick'   => $this->tick,
                'colony_id' => $colonyId,
                'tech_id' => $techId,
                'order' => 'add',
                'ap_ordered' => $apOrdered+1,
                'is_final_step' => 0, // ??
            );
            $table = $this->getTable('order');
            $result = $table->save($order);

        } elseif ($tech->ap_for_levelup == 0) {
            // build immediately => only hire/fire advisors!
            $table = $this->getTable('possession');
            $possess = $table->fetchAll(array(
                'colony_id' => $colonyId,
                'tech_id' => $techId,
            ));
            foreach ($possess as $poss) {
                $poss->level = $poss->level + 1;
                $result = $table->save($poss);
            }
        }

//        $cache = $this->getServiceLocator()->get('cache');
//        $cache->clean(Zend_Cache::CLEANING_MODE_MATCHING_TAG, array('techtree','orders'));

        return (bool) $result;
    }

    /**
     * Check requirements and add an order to reduce technology level by 1.
     *
     * what this function does:
     *      - check if minimum is reached
     *      - if check is successfull:
     *          - store an order to reduce 1 level
     *      - else:
     *          - throws exception
     *      - throws exception if an error occurs
     *
     * what this function NOT does:
     *      - reduce a level immediatly
     *
     * @param  integer $techId
     * @param  integer $colonyId
     * @return boolean
     * @throws \Techtree\Model\Exception   if invalid parameter(s)
     */
    private function technologyLevelDown($techId, $colonyId, $ap)
    {
        $this->_validateId($techId);
        $this->_validateId($colonyId);

        // check if 0 is reached
        $possessLevel = $this->getLevelByTechnologyId($techId,$colonyId);
        if ( $possessLevel <= 0 ) {
            throw new Exception('exception_MinimumReached');
        }

        // create the order and save it:
        // (it is always just 1tick)
        $order = $this->createOrder(array(
            'tick' => $this->tick,
            'colony_id' => $colonyId,
            'tech_id' => $techId,
            'order' => 'remove',
            'ap_ordered' => 1,
            'is_final_step' => 1,
        ));
        $result = $order->save();
        return (bool) $result;
    }

    /**
     *
     * @param unknown $techId
     * @param unknown $colonyId
     * @return boolean
     */
    private function cancelOrders($techId, $colonyId)
    {
        $this->_validateId($techId);
        $this->_validateId($colonyId);

        $entity = array(
            'tick' => $this->tick,
            'tech_id' => $techId,
            'colony_id' => $colonyId
        );
        $table = $this->getTable('order');
        $result = $table->delete($entity);
        return (bool) $result;
    }

    /**
     * Increase Tech Amount immediately without giving an order!
     * ATTENTION: use "technologyLevelUp/Down"-functions for techtree actions instead
     *
     * @param integer $colonyId
     * @param integer $techId
     * @param integer $amount
     */
    public function increaseAmount($colonyId, $techId, $amount)
    {
        $this->_validateId($colonyId);
        $this->_validateId($techId);
        $amount = (int) $amount;

        $table = $this->getTable('possessions');
        $row = $table->fetchRow("colony_id = $colonyId AND tech_id = $techId");
        if ( !empty($row) ){
            //update
            $row = $row->toArray();
            $data = $row['count'] + $amount;
            $data  = array('count' => $data);
            $where = array(
                $table->getAdapter()->quoteInto('colony_id = ?', $colonyId),
                $table->getAdapter()->quoteInto('tech_id = ?', $techId)
            );
            $result = $table->update($data,$where);
        }
        else {
            //insert
            $data = array(
                'colony_id'   => $colonyId,
                'tech_id' => $techId,
                'count'    => $amount
            );

            $result = $table->insert($data);
        }

//         $cache = $this->getServiceLocator()->get('cache');
//         $cache->clean(Zend_Cache::CLEANING_MODE_MATCHING_TAG, array('techtree','possessions'));

        return (bool) $result;
    }

    /**
     *
     * @param $colonyId
     * @param $techId
     * @param $amount
     */
    public function decreaseAmount($colonyId, $techId, $amount)
    {
        return $this->increaseAmount($colonyId, $techId, -$amount);
    }

    /**
     * Cancel Order.
     * - if actual tick: give back the resources
     * - @TODO else: give back half resources
     * - cancel the order
     *
     * @param int $techId
     * @param int $colonyId
     * @throws \Techtree\Model\Exception if invalid parameter(s)
     */
    public function cancelOrder($techId, $colonyId, $includePast = false)
    {
        $this->_validateId($techId);
        $this->_validateId($colonyId);

        $where = "tech_id = $techId AND colony_id = $colonyId";
        if ( $includePast !== true ) {
            $where .= " AND tick >= " . $this->tick;
        }
        $orders = $this->getOrders($where);

        if ($orders->valid() AND $orders->tick >= $tick) {
            // give back the payed resources:
            $costs = $this->getCostsByTechnologyId($techId);
            $resourcesGW = new \Resources\Model\Gateway();
            $resourcesGW->returnCosts($costs, $colonyId);
        }

        while ( $orders->valid() ) {
            $orders->current()->delete();
            $orders->next();
        }

//        $cache = $this->getServiceLocator()->get('cache');
//        $cache->clean(Zend_Cache::CLEANING_MODE_MATCHING_TAG, array('techtree','orders'));
//        $cache->clean(Zend_Cache::CLEANING_MODE_MATCHING_TAG, array('techtree','possessions'));
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

        $poss  = $this->getPossessionsByColonyId($colonyId)->toArray();
        $rqrmnts = $this->getRequirementsByTechnologyId($techId);

        // compare possession with requirements:
        foreach ($rqrmnts as $rq)
        {
            $id = $rq->tech_id;
            if ( !isset($poss->$id) || $rq->required_tech_count > $poss[$id]['count']) {
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
        return $this->getGateway('resources')->check($costs, $colonyId);
    }

    /**
     *
     * @param unknown $techId
     * @param unknown $colonyId
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
     * @param  integer $colonyId
     * @return ResultSet
     */
    public function getOrders($where = null, $order = null, $count = null, $offset = null)
    {
        $dbTable = $this->getTable('order');
        return $dbTable->fetchAll($where, $order, $count, $offset);
    }

    /**
     * get order by technology id for current tick
     *
     * @param unknown $techId
     * @param unknown $colonyId
     * @return mixed|NULL
     */
    public function getOrderByTechnologyId($techId, $colonyId)
    {
        $this->_validateId($techId);
        $this->_validateId($colonyId);

        $tick = $this->getTick();
        $dbTable = $this->getTable('order');
        $rowset = $dbTable->fetchAll("tick = $tick AND tech_id = $techId AND colony_id = $colonyId");
        if ($rowset->valid() and $rowset->count() > 0) {
            return $rowset->current();
        }
        return null;
    }

    /**
     * Get the maximum amount of building orders. This is depending on the amount
     * of engineers the player has. Without any engineers maximum is one order.
     * Every engineer brings one possible order more.
     *
     * @param  integer $colonyId
     * @return integer
     */
    public function getMaxBuildingOrders($colonyId)
    {
        return $this->_getMaxOrders('building', $colonyId);
    }

    /**
     * Get the maximum amount of research orders. This is depending on the amount
     * of scientists the player have. Without any scientist no research is possible.
     * Every scientist brings one possible order more.
     *
     * @param  integer $colonyId
     * @return integer
     */
    public function getMaxResearchOrders($colonyId)
    {
        return $this->_getMaxOrders('research', $colonyId);
    }

    /**
     * Get the maximum amount of research orders. This is depending on the amount
     * of scientists the player have. Without any scientist no research is possible.
     * Every scientist brings one possible order more.
     *
     * @param  integer $colonyId
     * @return integer
     */
    public function getMaxFleetOrders($colonyId)
    {
        return $this->_getMaxOrders('fleet', $colonyId);
    }

    /**
     * Get the maximum amount of diplomats.
     *
     * @param  integer $colonyId
     * @return integer
     */
    public function getMaxDiplomacyOrders($userId)
    {
        $galaxyGw = new \Galaxy\Model\Gateway();
        try {
            $colony =  $galaxyGw->getColonyByUserId($userId);
            return $this->_getMaxOrders('diplomacy', $colony->id);
        } catch (\Galaxy\Model\Exception $e) {
            return 0;
        }
    }

    /**
     *
     * @param numeric $userId
     * @return numeric
     */
    public function getMaxSpyActions($userId)
    {
        $galaxyGw = new \Galaxy\Model\Gateway();
        try {
            $colony = $galaxyGw->getColonyByUserId($userId);
            return $this->_getMaxOrders('spy', $colony->id);
        } catch (\Galaxy\Model\Exception $e) {
            return 0;
        }
    }

    /**
     *
     * @param  string  $type
     * @param  integer $colonyId
     * @return number|number
     */
    protected function getTotalActionPoints($type, $colonyId)
    {
        $this->_validateId($colonyId);

        switch (strtolower($type)) {
            case 'building':  $techId = self::ADVISOR_ENGINEER_TECHID; break;
            case 'research':  $techId = self::ADVISOR_SCIENTIST_TECHID; break;
            case 'fleet':     $techId = self::ADVISOR_FLEETCOMMANDER_TECHID; break;
            case 'diplomacy': $techId = self::ADVISOR_DIPLOMAT_TECHID; break;
            //case 'military': $techId = self::..;break;
            case 'spy':       $techId = self::ADVISOR_CHIEFOFINTELLIGENCE; break;
            default:  $techId = self::ADVISOR_ENGINEER_TECHID; break;
        }

        $level = $this->getLevelByTechnologyId($techId, $colonyId);

        return ( $level+1 );
    }

    /**
     * get available action points for current tick
     *
     * @param  string  $type
     * @param  integer $colonyId
     * @return number|number
     */
    protected function getAvailableActionPoints($type, $colonyId)
    {
        $this->_validateId($colonyId);

        $totalAP = $this->getTotalActionPoints($type, $colonyId);

        $orders = $this->getTable('order')->fetchAll(array('tick'=>$this->tick,'colony_id'=>$colonyId));

        $usedAP = 0;
        foreach ($orders as $order) {
            $usedAP += $order->ap_ordered;
        }

        return ( $totalAP-$usedAP+1 );
    }

    /**
     *
     * @param  numeric $userId
     * @return array
     */
    public function countTechsByType($userId) {

        $result = array(
            'buildings' => 0,
            'research' => 0,
            'ships' => 0,
            'advisors' => 0
        );

        $possessions = $this->getPossessionsByUserId($userId);

        while ($possessions->valid()) {
            switch ($possessions->type) {
                case 'building': $result['buildings'] += $possessions->count; break;
                case 'research': $result['research']  += $possessions->count; break;
                case 'ships':    $result['ships']     += $possessions->count; break;
                case 'advisor':  $result['advisors']  += $possessions->count; break;
                default: break;
            }
            $possessions->next();
        }

        return $result;

    }

    /**
     *
     * @param  numeric $userId
     * @return array
     */
    public function countTechsByPurpose($userId) {
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
                case 'civil':     $result['civil']    += $possessions->count; break;
                case 'industry':  $result['industry'] += $possessions->count; break;
                case 'economy':   $result['economy']  += $possessions->count; break;
                case 'politics':  $result['politics'] += $possessions->count; break;
                case 'military':  $result['military'] += $possessions->count; break;
                default: break;
            }
            $possessions->next();
        }

        return $result;
    }

    /**
     *
     * @param numeric $userId
     * @param numeric $sinceTick
     */
    public function getProcessedOrders($userId, $sinceTick = null)
    {
        $this->_validateId($userId);

        $lastTick = $this->tick - 1;

        if ( !is_numeric($sinceTick) ) {
            $sinceTick = $lastTick;
        }

        $galaxyGateway = new \Galaxy\Model\Gateway();
        $colonies = $galaxyGateway->getColoniesByUserId($userId);

        if ($colonies->count() == 1) {
            $whereColo = "colony_id = $colonies->id";
        } else {
            while ($colonies->valid()) {
                $colonyIds[] = $colonies->id;
                $colonies->next();
            }
            $colonyIds = implode($colonyIds, ',');
            $whereColo = "colony_id IN ($colonyIds)";
        }

        return $this->getOrders("$whereColo AND tick >= $sinceTick AND tick <= $lastTick AND is_final_step = 1 AND was_processed");
    }

    /**
     * Find all processed orders and notify innn and eventDispatcher about them.
     * Set is_notified to true to avoid a double notification.
     *
     * - set innn events
     * - set event dispatcher events
     *
     * @param numeric $userId
     */
    public function notifyAboutProcessedOrders($userId)
    {
        $this->_validateId($userId);


        $innnGw = new \Innn\Model\Gateway();
        $orders = $this->getProcessedOrders($userId);

        $tick = $this->tick;
//         $cache = $this->getServiceLocator()->get('cache');

        while ( $orders->valid() ) {

            if ( $orders->is_notified == 0 ) {

                $order = $orders->current();

                if ( $order->order == 'sub' ) {
                    $event = 'techtree.level_down_finished';
                } else {
                    $event = 'techtree.level_up_finished';
                }

                // set innn event:
                $data = array(
                    'user_id' => $userId,
                    'tick' => $tick,
                    'event' => $event,
                    'parameters' => serialize(array('tech_id' => $order->tech_id, 'colony_id' => $order->colony_id))
                );

                $innnEvent = $innnGw->createEvent($data);
                $innnEvent->save();

                // notify event dispatcher:
                $event = new sfEvent($this, $event );
                $dispatcher = $this->getServiceLocator()->get('eventDispatcher');
                $dispatcher->notify($event);

                $order->is_notified = 1;
                $order->save();

                //$cache->clean(Zend_Cache::CLEANING_MODE_MATCHING_TAG, array('techtree','orders'));
            }
            $orders->next();
        }
    }

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