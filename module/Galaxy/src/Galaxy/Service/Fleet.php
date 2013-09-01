<?php
namespace Galaxy\Service;

class Gateway extends \Nouron\Service\AbstractService
{
    public function __construct($tick, array $tables, array $gateways = array())
    {
        $this->setTick($tick);
        $this->setTables($tables);
        $this->setGateways($gateways);
    }

    /**
     * Get all systems.
     *
     * @param  string|array $where  OPTIONAL
     * @param  string|array $order  OPTIONAL
     * @param  integer $offset  OPTIONAL
     * @param  integer $limit   OPTIONAL
     * @return ResultSet
     */
    public function getSystems($where = null, $order = null, $offset = null, $limit =null)
    {
        return $this->getTable('system')->fetchAll($where, $order, $offset, $limit);
    }

    /**
     * @return \Fleets\Entity\Fleet
     */
    public function getFleet($fleetId)
    {
        $this->_validateId($fleetId);
        return $this->getTable('fleet')->getEntity($fleetId);
    }

    /**
     *
     * @param \Galaxy\Entity\Fleet $entity
     */
    public function saveFleet($entity)
    {
        return $this->getTable('fleet')->save($entity);
    }

    /**
     *
     * @param \Galaxy\Entity\Fleet $entity
     */
    public function saveFleetOrder($entity)
    {
        return $this->getTable('fleetorder')->save($entity);
    }

    /**
     * Get all fleets from a user.
     *
     * @param  integer    $userId
     * @return ResultSet
     */
    public function getFleetsByUserId($userId)
    {
        $this->_validateId($userId);
        return $this->getTable('fleet')->fetchAll('user_id = ' . $userId)->getArrayCopy();
    }

    /**
     * Get all fleets from coordinates of an colony, system object or system entity.
     *
     * @param  string  $entityType
     * @param  integer $id
     * @return ResultSet
     */
    public function getFleetOrdersByFleetIds(array $fleetIds)
    {
        $where = 'fleet_id IN (' . implode ( ',' , $fleetIds ) . ')';
        return $this->getTable('fleetorder')->fetchAll($where);
    }

    /**
     * Get all fleets from coordinates of an colony, system object or system entity.
     *
     * @param  string  $entityType
     * @param  integer $id
     * @return ResultSet
     */
    public function getFleetsByEntityId($entityType, $id)
    {
        $this->_validateId($id);

        switch (strtolower($entityType)) {
            case 'colony': $table = $this->getTable('colony'); break;
            case 'object': $table = $this->getTable('systemobject'); break;
            case 'system': $table = $this->getTable('system'); break;
            default: return array(); break;
        }

        $entity = $table->getEntity($id);
        return $this->getByCoordinates('fleets', array($entity['x'],$entity['y']))->getArrayCopy();
    }

    /**
     * Add a fleet Order.
     *
     * @todo: just move is working now - implement all the order types!
     *
     * @param  string   $order
     * @param  integer|array|string|\Galaxy\Entity\Colony|\Galaxy\Entity\Fleet
     *                  $destination     The Id or Coords of a colony or fleet
     * @param  array    $additionalData  OPTIONAL array with optional target data like trade orders etc.
     * @return boolean
     * @throws \Exception
     */
    public function addOrder($fleet, $order, $destination, $additionalData = null)
    {
        // check the parameter '$fleet':
        if ( is_numeric($fleet) ) {
            $fleetId = (int) $fleet;
            $fleet = $this->getFleet($fleetId);
        } else {
            $fleetId = $fleet['id'];
        }

        // check the parameter $order:
        if ( !in_array($order, array('move','trade','hold','convoy','defend','attack','join','devide')) ) {
            throw new \Exception('Unknown command given: ' . $order);
        }


        // get coordinates:
        if ( !(is_array($destination) && (array_keys($destination) == array(0,1,2) )) ){

            if (is_numeric($destination)) {

                //destination is a fleet id or colony id
                $destination = (int) $destination;
                switch (strtolower($order)) {
                    case 'move':   $object = $this->getColony($destination); break;
                    case 'trade':  $object = $this->getColony($destination); break;
                    case 'hold':   $object = $this->getColony($destination); break;
                    case 'convoy': $object = $this->getFleet($destination);  break;
                    case 'defend': $object = $this->getFleet($destination);  break;
                    case 'attack': $object = $this->getFleet($destination);  break;
                    case 'join':   $object = $this->getFleet($destination);  break;
                    case 'devide': break; // nothing
                    default:       break; // nothing
                }

                $destinationCoords = $object->getCoords();

            } elseif ( $destination instanceof \Galaxy\Entity\Colony || $destination instanceof \Galaxy\Entity\Fleet ) {
                // destination is an object:
                $destinationCoords = $destination->getCoords();
            } elseif ( is_array(@unserialize($destination)) ) {
                // destination is a serialized coords array:
                $destinationCoords = unserialize($destination);
            } elseif ( is_array(@json_decode($destination)) ) {
                // destination is a json encoded coords array:
                $destinationCoords = json_decode($destination);
            } else {
                throw new \Galaxy\Entity\Exception('Invalid variable type of $destination. $destination has to be an id, object or 3-dimensional array.');
            }

            $destination = $destinationCoords;
            unset($destinationCoords);
        }

        // get coords of fleet and destination coords:
        $coords = array($fleet['x'], $fleet['y'], $fleet['spot']);

        // if not, then get the paths and add the movement steps:
        $path = $this->getPath( $coords, $destination, 1); #$fleet->getTravelSpeed() );
        $this->_storePathInDb($fleetId, $path, $order, $additionalData);

    }

    /**
     *
     * @param array $path
     * @throws Galaxy_Model_Exception
     */
    protected function _storePathInDb($fleetId, $path, $order, $additionalData)
    {
        try {
            $db = $this->getTable('fleetorder')->getAdapter()->getDriver()->getConnection();
            $db->beginTransaction();

            // first remove the currently set future orders:
            $where = "fleet_id = {$fleetId} AND tick >= {$this->tick}";
            $ordersTable = $this->getTable('fleetorder');
            $ordersTable->delete($where);

            // set the new path
            $i = 1;
            foreach ($path as $tickNr => $tmpCoords) {
                // create a move order for one tick and save to db
                $tmpOrder = ($i == count($path)) ? $order : 'move';
                $cmdArray = array(
                    'fleet_id' => $fleetId,
                    'tick'  => $tickNr,
                    'order' => strtolower($tmpOrder),
                    'coordinates' => serialize($tmpCoords),
                );

                if ($i == count($path) && is_array($additionalData)) {
                    $cmdArray['data'] = serialize($additionalData);
                }

                $result = $ordersTable->save($cmdArray);
                unset($cmd);
                $i++;
            }
            $db->commit();
        } catch (Exception $e) {
            $db->rollBack();
            throw new \Galaxy\Service\Exception($e->getMessage());
        }
    }

    /**
     * Transfer an amount of technology from colony to fleet.
     * (Vice versa when amount is negative!)
     *
     * @param  int|object   $colony
     * @param  int|object   $fleet
     * @param  integer      $techId
     * @param  integer      $amount
     * @param  boolean      $isCargo       OPTIONAL set true if use fleet cargo (not fleet itself)
     * @param  boolean      $isTradeOffer  OPTIONAL set true to fullfill an existing trade offer
     *
     * @return int          Count of transfered Technologies
     */
    public function transferTechnology($colony, $fleet, $techId, $amount, $isCargo = false, $isTradeOffer = false)
    {
        if (is_numeric($colony)) {
            $colony = $this->getColony($colony);
        }

        if (is_numeric($fleet)) {
            $fleet = $this->getFleet($fleet);
        }

        $colonyCoords = array($colony['x'],$colony['y']);#,$colony['spot']);
        $fleetCoords = array($fleet['x'],$fleet['y']);#,$fleet['spot']);

        if (serialize($colonyCoords) == serialize($fleetCoords)) {

            if ( !$isTradeOffer ) {
                $techOnColony = $this->getColonyTechnology(array('colony_id' => $colony['id'], 'tech_id' => $techId));
            } else {
//                 $tradeGw      = new Trade\Service\Gateway();
//                 $techOnColony = $tradeGw->getTechnologyOffer($colony['id'], $techId);
//                 if ( $techOnColony == null) {
//                     return 0;
//                 }
            }

            $techInFleet  = $this->getFleetTechnology(array('fleet_id' => $fleet['id'], 'tech_id' => $techId, 'is_cargo' => $isCargo));

            if ($amount >= 0 ) {
                // check if there are enough techs on the colony:
                if ($amount > $techOnColony['count']) {
                    // only remove the count of techs that really exists on the colony;
                    $amount = $techOnColony['count'];
                }
            } else {
                // check if there are enough techs in the fleet:
                if ($amount < -$techInFleet['count']) {
                    // only remove the count of techs that really exists in the fleet:
                    $amount = -$techInFleet['count'];
                }
            }

            try {
                $db = $this->getTable('fleet')->getAdapter()->getDriver()->getConnection();
                $db->beginTransaction();
                $techOnColony['count'] = $techOnColony['count'] - $amount;
                $this->getTable('colonytechnology')->save($techOnColony);
                $techInFleet['count'] = $techInFleet['count'] + $amount;
                $this->getTable('fleettechnology')->save($techInFleet);
                $db->commit();

            } catch (Exception $e) {
                $db->rollBack();
                throw new Exception( $e->getMessage() );
            }

            return abs($amount);
        }
    }

    /**
     * Transfer an amount of resources from colony to fleet.
     * (Vice versa when amount is negative!)
     *
     * @param  int|object  $colony
     * @param  int|object  $fleet
     * @param  integer     $resId
     * @param  integer     $amount
     * @param  boolean     $isTradeOffer  OPTIONAL set true to fullfill an existing trade offer
     * @return int    Count of transfered res
     */
    public function transferResource($colony, $fleet, $resId, $amount, $isTradeOffer = false)
    {
        if (is_numeric($colony)) {
            $colony = $this->getColony($colony);
        }

        if (is_numeric($fleet)) {
            $fleet = $this->getFleet($fleet);
        }

        if (serialize($colony->getCoords()) == serialize($fleet->getCoords())) {

            if ( !$isTradeOffer ) {
                $resGw       = new Resources\Service\Gateway();
                $resOnColony = $resGw->getColonyResource(array('colony_id' => $colony['id'], 'resource_id' => $resId));
            } else {
                $tradeGw     = new Trade\Service\Gateway();
                $resOnColony = $tradeGw->getResourceOffer($colony['id'], $resId);
                if ( $resOnColony == null) {
                    return 0;
                }
            }
            $resInFleet  = $this->getFleetResource(array('fleet_id' => $fleet['id'], 'resource_id' => $resId));

            if ($amount >= 0 ) {
                // check if there are enough res on the colony:
                if ($amount > $resOnColony['amount']) {
                    // only remove the count of res that really exists in the fleet:
                    $amount = $resOnColony['amount'];
                }
            } else {
                // check if there are enough res in the fleet:
                if ($amount < -$resInFleet['amount']) {
                    // only remove the count of res that really exists in the fleet:
                    $amount = -$resInFleet['amount'];
                }
            }

            try {
                $db = $this->getTable('fleets')->getAdapter()->getDriver()->getConnection();
                $db->beginTransaction();

                $resOnColony['amount'] = $resOnColony['amount'] - $amount;
                $resOnColony->save();
                $resInFleet['amount'] = $resInFleet['amount'] + $amount;
                $resInFleet->save();

                $db->commit();

            } catch (Exception $e) {
                $db->rollBack();
                throw new Exception( $e->getMessage() );
            }

            return abs($amount);
        }
    }

    /**
     * Get one specific technology from a fleet specified by given compound primary key.
     * One technology from a fleet - not more!
     * ATTENTION: This function allways return a fleetstechnology object even if the
     * tech is not in the fleet!
     *
     * @param  array $keys  The compound primary key in form: array('fleet_id' => 1, 'tech_id' => 2)
     * @return \Galaxy\Entity\FleetTechnology | array
     */
    public function getFleetTechnology(array $keys)
    {
        $table = $this->getTable('fleettechnology');
        $fleettech = $table->fetchAll($keys);
        $result = $fleettech->current();
        if (empty($result)) {
            return array(
                'fleet_id' => $keys['fleet_id'],
                'tech_id' => $keys['tech_id'],
                'count' => 0,
                'is_cargo' =>  $keys['is_cargo'],
            );
        } else {
            return $result;
        }
    }

    /**
     * Get all ships and other technologies from a fleet.
     *
     * @param $where
     * @return \ResultSet
     */
    public function getFleetTechnologies($where)
    {
        $table = $this->getTable('fleettechnology');
        return $table->fetchAll($where);
    }

//     /**
//      * Get one specific resource from a fleet specified by given compound primary key.
//      * One resource from a fleet - not more!
//      *
//      * @param  array $keys  The compound primary key in form: array('fleet_id' => 1, 'resource_id' => 2)
//      * @return \Galaxy\Entity\Fleets_Resource
//      */
//     public function getFleetResource(array $keys)
//     {
//         if (!isset($keys['fleet_id']) || !isset($keys['resource_id'])) {
//             throw new Techtree_Model_Exception('Not a valid compound primary key.');
//         }

//         $table = $this->getDbView('Fleets_Resources');
//         foreach ($table->info('primary') as $id) {
//             $val = $keys[$id];
//             $sql[] = "$id = $val";
//         }
//         $result = $table->fetchAll($sql);
//         if ($result->valid()) {
//             $row = $result->current();
//         } else {
//             $row = $keys;
//         }
//         return new \Galaxy\Entity\Fleets_Resource($row, $this);
//     }

//     /**
//      *
//      *
//      * @param  string|array|object $fleets
//      * @param  boolean $past   Include orders in the past?
//      * @return \Galaxy\Entity\fleetorder
//      */
//     public function getFleetsOrders($fleets, $past = false)
//     {
//         if ($fleets instanceof \Galaxy\Entity\Fleets) {
//             $fleets = $fleets->getArrayCopy();
//         }

//         if (is_array($fleets)) {
//             $fleets = implode(',', array_keys($fleets));
//         }

//         if (!is_string($fleets)) {
//             throw new Exception('fleet must be object, array or string');
//         }

//         if (empty($fleets)) {
//             return new \Galaxy\Entity\fleetorder(array(), $this);
//         }

//         $table = $this->getTable('fleetorder');
//         if (!$past) {
//             $tick = $this->getTick();
//             $orders = $table->fetchAll("fleet_id IN ($fleets) AND tick >= $tick");
//         } else {
//             $orders = $table->fetchAll("fleet_id IN ($fleets)");
//         }

//         return new \Galaxy\Entity\fleetorder($orders, $this);
//     }

    /**
     *
     * @param string $where
     * @param string $order
     * @param string $count
     * @param string $offset
     * @return ResultSet
     */
    public function getOrders($where = null, $order = null, $count = null, $offset = null)
    {
        #$cache = Zend_Registry::get('cache');
        #$cacheName = 'fleet_orders_' . md5(serialize($where).serialize($order).$count.$offset);
        #if (!($result = $cache->load($cacheName))) {
            $table = $this->getTable('fleetorder');
            $result = $table->fetchAll($where, $order, $count, $offset);
            #$cache->save($result, $cacheName, array('fleets', 'orders'));
        #}
        return $result;
    }

//     /**
//      *
//      * @param numeric $userId
//      * @param numeric $sinceTick
//      */
//     public function getProcessedOrders($userId, $sinceTick = null)
//     {
//         $this->_validateId($userId);

//         $lastTick = $this->getTick() - 1;

//         if ( !is_numeric($sinceTick) ) {
//             $sinceTick = $lastTick;
//         }

//         return $this->getOrders("tick >= $sinceTick AND tick <= $lastTick AND sOrder <> 'move' AND bProcessed = 1");
//     }

//     /**
//      * Find all processed orders and notify innn and eventDispatcher about them.
//      * Set has_notified to true to avoid a double notification.
//      *
//      * - set innn events
//      * - set event dispatcher events
//      *
//      * @param numeric $userId
//      */
//     public function notifyAboutProcessedOrders($userId)
//     {
//         $this->_validateId($userId);
//         $tick = $this->getTick();
//         $innnGw = new \Innn\Service\Gateway();
//         $orders = $this->getProcessedOrders($userId);
//         $cache = Zend_Registry::get('cache');
//         while ( $orders->valid() ) {

//             if ( $orders->has_notified == 0 ) {

//                 $order = $orders->current();
//                 $coords = $order->getCoords();
//                 switch ($order->sOrder) {
//                     case 'trade': $event = 'galaxy.trade';break;
//                     case 'fight': $event = 'galaxy.fight';break;
//                     default:break;
//                 }

//                 $orderData = @unserialize($order->sData);

//                 // set innn event:
//                 $data = array(
//                         'nUser' => $userId,
//                         'tick' => $tick,
//                         'sEvent' => $event,
//                         'sParameters' => serialize(array('colony_id' => $orderData['colony_id']))
//                 );

//                 $innnEvent = $innnGw->createEvent($data);
//                 $innnEvent->save();

//                 // notify event dispatcher:
//                 $event = new sfEvent($this, $event );
//                 $dispatcher = Zend_Registry::get('eventDispatcher');
//                 $dispatcher->notify($event);

//                 // mark order as notified
//                 $order->has_notified = 1;
//                 $order->save();

//                 // clear cache
//                 $cache->clean(Zend_Cache::CLEANING_MODE_MATCHING_TAG, array('fleets','orders'));
//             }
//             $orders->next();
//         }
//     }
}