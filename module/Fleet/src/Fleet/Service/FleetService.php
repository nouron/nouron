<?php
namespace Fleet\Service;

use Fleet\Entity\Fleet;
use Fleet\Entity\FleetPersonell;
use Fleet\Entity\FleetShip;
use Fleet\Entity\FleetResearch;
use Fleet\Entity\FleetResource;
use Nouron\Model\ResultSet;

class FleetService extends \Galaxy\Service\Gateway
{
    public function __construct($tick, array $tables, array $services = array())
    {
        parent::__construct($tick, $tables, $services, array());
    }

    /**
     * @return string
     */
    public function getFleet($fleetId)
    {
        $this->_validateId($fleetId);
        return $this->getTable('fleet')->getEntity($fleetId);
    }

    /**
     *
     * @param Fleet $entity;
     */
    public function saveFleet($entity)
    {
        return $this->getTable('fleet')->save($entity);
    }

    /**
     *
     * @param Fleet $entity;
     */
    public function saveFleetOrder($entity)
    {
        return $this->getTable('fleetorder')->save($entity);
    }

    /**
     * Get all fleets from coordinates of an colony, system object or system entity.
     *
     * @return ResultSet
     */
    public function getFleetOrdersByFleetIds(array $fleetIds)
    {
        $where = 'fleet_id IN (' . implode ( ',' , $fleetIds ) . ')';
        return $this->getTable('fleetorder')->fetchAll($where);
    }

    /**
     * Add a fleet Order.
     *
     * @todo: just move is working now - implement all the order types!
     *
     * @param  string   $order
     * @param  integer|array|string|\Galaxy\Entity\Colony|\Fleet\Entity\Fleet
     *                  $destination     The Id or Coords of a colony or fleet
     * @param  array    $additionalData  OPTIONAL array with optional target data like trade orders etc.
     * @return boolean|null
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
                    #case 'devide': break; // nothing
                    default:       $object = $this->getFleet($destination);  break;
                }

                $destinationCoords = $object->getCoords();

            } elseif ( $destination instanceof \Galaxy\Entity\Colony || $destination instanceof \Fleet\Entity\Fleet ) {
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
     * @param string $order
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

                $ordersTable->save($cmdArray);
                unset($cmd);
                $i++;
            }
            $db->commit();
        } catch (Exception $e) {
            $db->rollBack();
            throw new \Galaxy\Service\Exception($e->getMessage());
        }
    }

    public function transferShip($colony, $fleet, $shipId, $amount, $isCargo = false)
    {
        return $this->transferTechnology('ship', $colony, $fleet, $shipId, $amount, $isCargo);
    }

    public function transferResearch($colony, $fleet, $researchId, $amount, $isCargo = true)
    {
        return $this->transferTechnology('research', $colony, $fleet, $researchId, $amount, $isCargo);
    }

    public function transferPersonell($colony, $fleet, $personellId, $amount, $isCargo = false)
    {
        return $this->transferTechnology('personell', $colony, $fleet, $personellId, $amount, $isCargo);
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
     * @param string $type
     *
     * @return int          Count of transfered Technologies
     */
    public function transferTechnology($type, $colony, $fleet, $techId, $amount)
    {
        if (is_numeric($colony)) {
            $colony = $this->getColony($colony);
        }

        if (is_numeric($fleet)) {
            $fleet = $this->getFleet($fleet);
        }

        $type = strtolower($type);
        switch ( $type ) {
            case 'ship':
                $colonyTypeTablename = 'colonyship';
                $fleetTypeTablename = 'fleetship';
                $typeKey = 'ship_id';
                break;
            case 'research':
                $colonyTypeTablename = 'colonyresearch';
                $fleetTypeTablename = 'fleetresearch';
                $typeKey = 'research_id';
                break;
            case 'personell':
                $colonyTypeTablename = 'colonypersonell';
                $fleetTypeTablename = 'fleetpersonell';
                $typeKey = 'personell_id';
                break;
            default:
                throw new Exception("Invalid parameter 'type' for transferTechnology.");
        }

        $colonyCoords = $colony->getCoords();#array($colony['x'],$colony['y']);#,$colony['spot']);
        $fleetCoords  = $fleet->getCoords();#array($fleet['x'],$fleet['y']);#,$fleet['spot']);

        if (serialize($colonyCoords) == serialize($fleetCoords)) {

/*            if ( !$isTradeOffer ) {
                $techOnColony = $this->getColonyTechnology($type, array('colony_id' => $colony->getId(), $typeKey => $techId));
            } else {
//                 $tradeGw      = new Trade\Service\Gateway();
//                 $techOnColony = $tradeGw->getTechnologyOffer($colony['id'], $techId);
//                 if ( $techOnColony == null) {
//                     return 0;
//                 }
            }*/

            $keys = array(
                'colony_id' => $colony->getId(),
                $typeKey => $techId
            );
            $table = $this->getTable($colonyTypeTablename);
            $result = $table->fetchAll($keys)->current();
            if (empty($result)) {
                $techOnColony = array(
                    'colony_id' => $keys['colony_id'],
                    $typeKey => $keys[$typeKey],
                    'level' => 0,
                );
            } else {
                $techOnColony = $result->getArrayCopy();
            }

            $techsInFleet = $this->_gatherFleetTechnologyInformations($fleet->getId(), $type);
            $techInFleet  = $techsInFleet[$techId];
            if ($amount >= 0 ) {
                // check if there are enough techs on the colony:
                if ($amount > $techOnColony['level']) {
                    // only remove the count of techs that really exists on the colony;
                    $amount = $techOnColony['level'];
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
                $techOnColony['level'] = $techOnColony['level'] - $amount;
                $this->getTable($colonyTypeTablename)->save($techOnColony);
                $techInFleet['count'] = $techInFleet['count'] + $amount;
                $this->getTable($fleetTypeTablename)->save($techInFleet);
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
    public function transferResource($colony, $fleet, $resId, $amount)
    {
        if (is_numeric($fleet)) {
            $fleet = $this->getFleet($fleet);
        }

        if (serialize($colony->getCoords()) == serialize($fleet->getCoords())) {
        /*  if ( !$isTradeOffer ) {*/
                $resOnColony = $this->getColonyResource(array('colony_id' => $colony->id, 'resource_id' => $resId));
/*            } else {
                $tradeGw     = new Trade\Service\Gateway();
                $resOnColony = $tradeGw->getResourceOffer($colony['id'], $resId);
                if ( $resOnColony == null) {
                    return 0;
                }
            }*/
            $resInFleet  = $this->getFleetResource(array('fleet_id' => $fleet->id, 'resource_id' => $resId));

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

            $colonyresourceTable = $this->getTable('colonyresource');
            $fleetresourceTable  = $this->getTable('fleetresource');
            $db = $fleetresourceTable->getAdapter()->getDriver()->getConnection();
            try {
                $db->beginTransaction();
                $resOnColony['amount'] = $resOnColony['amount'] - $amount;
                $colonyresourceTable->save($resOnColony);
                $resInFleet['amount']  = $resInFleet['amount'] + $amount;
                $fleetresourceTable->save($resInFleet);
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
     * ATTENTION: This function allways return an entity even if the
     * entity is not in the fleet!
     *
     * @param  array $key  The compound primary key in form: array('fleet_id' => 1, 'tech_id' => 2)
     * @param  boolean $forceResultEntity  create Entity even if result is empty
     * @return FleetShip
     */
    public function getFleetShip(array $key, $forceResultEntity = false)
    {
        $result = $this->getTable('fleetship')->select($key)->current();
        if (empty($result) && $forceResultEntity) {
            $result = new FleetShip();
            $result->setFleetId($key['fleet_id']);
            $result->setShipId($key['ship_id']);
            $result->setCount(0);
        }
        return $result;
    }

    /**
     * similar to getFleetShip()
     *
     * @param  array $key  The compound primary key in form: array('fleet_id' => 1, 'research_id' => 2)
     * @param  boolean $forceResultEntity  create Entity even if result is empty
     * @return FleetResearch
     */
    public function getFleetResearch(array $key, $forceResultEntity = false)
    {
        $result = $this->getTable('fleetresearch')->select($key)->current();
        if (empty($result) && $forceResultEntity) {
            $result = new FleetResearch();
            $result->setFleetId($key['fleet_id']);
            $result->setResearchId($key['research_id']);
            $result->setCount(0);
        }
        return $result;
    }

    public function getFleetShips($where)
    {
        return $this->getTable('fleetship')->select($where);
    }

    public function getFleetShipsByFleetId($fleetId, $isCargo = null)
    {
        $this->_validateId($fleetId);
        $where = array('fleet_id' => $fleetId);
        if ($isCargo != null) {
            $where['is_cargo'] = (bool) $isCargo;
        }
        return $this->getFleetShips($where);
    }

    /**
     * similar to getFleetShip()
     *
     * @param  array $keys  The compound primary key in form: array('fleet_id' => 1, 'research_id' => 2)
     * @return FleetResearch
     */
    public function getFleetResearches($where)
    {
        return $this->getTable('fleetresearch')->select($where);
    }

    public function getFleetResearchesByFleetId($fleetId, $isCargo = null)
    {
        $this->_validateId($fleetId);
        $where = array('fleet_id'=> $fleetId);
        if ($isCargo != null) {
            $where['is_cargo'] = (bool) $isCargo;
        }
        return $this->getFleetResearches($where);
    }

    /**
     * similar to getFleetShip()
     *
     * @param  array $keys  The compound primary key in form: array('fleet_id' => 1, 'research_id' => 2)
     * @return FleetResearch
     */
    public function getFleetPersonell($where)
    {
        return $this->getTable('fleetpersonell')->select($where);
    }

    public function getFleetPersonellByFleetId($fleetId, $isCargo = null)
    {
        $this->_validateId($fleetId);
        $where = array('fleet_id' => $fleetId);
        if ($isCargo != null) {
            $where['is_cargo'] = (bool) $isCargo;
        }
        return $this->getTable('fleetpersonell')->select($where);
    }

    public function getFleetResources($where)
    {
        return $this->getTable('fleetresource')->select($where);
    }

    public function getFleetResourcesByFleetId($fleetId)
    {
        $this->_validateId($fleetId);
        return $this->getFleetResources(array('fleet_id'=>$fleetId));
    }

    /**
     * Get one specific resource from a fleet specified by given compound primary key.
     * One resource from a fleet - not more!
     * ATTENTION: This function allways return a fleetresource object even if the
     * tech is not in the fleet!
     *
     * @param  array $keys  The compound primary key in form: array('fleet_id' => 1, 'resource_id' => 2)
     * @return \Fleet\Entity\FleetResource | array
     */
    public function getFleetResource(array $keys)
    {
        $result = $this->getTable('fleetresource')->select($keys)->current();
        if (empty($result)) {
            return array(
                'fleet_id' => $keys['fleet_id'],
                'resource_id'  => $keys['resource_id'],
                'amount'    => 0,
            );
        } else {
            return $result->getArrayCopy();
        }
    }

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

    /**
     * Get all fleets from a user.
     *
     * @param  integer    $userId
     * @return ResultSet
     */
    public function getFleetsByUserId($userId)
    {
        $this->_validateId($userId);
        return $this->getTable('fleet')->fetchAll('user_id = ' . $userId);
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
            case 'colony':
                $table = $this->getTable('colony');
                break;
            case 'object':
                $table = $this->getTable('systemobject');
                break;
            case 'system':
                $table = $this->getTable('system');
                break;
            default:
                return array();
        }

        $entity = $table->getEntity($id);
        return $this->getByCoordinates('fleets', array($entity->getX(), $entity->getY()));
    }

    /**
     *
     * @param  string $type
     * @return array
     */
    private function _gatherFleetTechnologyInformations($fleetId, $type)
    {
        switch (strtolower($type)) {
            case 'research':
                $table = 'research';
                $id    = 'research_id';
                $func  = 'getFleetResearchesByFleetId';
                break;
            case 'ship':
                $table = 'ship';
                $id    = 'ship_id';
                $func  = 'getFleetShipsByFleetId';
                break;
            case 'personell':
                $table = 'personell';
                $id    = 'personell_id';
                $func  = 'getFleetPersonellByFleetId';
                break;
            default:
                return array(); # TODO: Exception
        }

        $entities  = $this->getTable($table)->fetchAll()->getArrayCopy('id');
        $fleetEntities = $this->$func($fleetId)->getArrayCopy(array('fleet_id', $id));
        $results = array();
        foreach ($entities as $id => $entity) {
            if (array_key_exists($id, $fleetEntities[$fleetId])) {
                $entities[$id] = $entities[$id] + $fleetEntities[$fleetId][$id];
            }
            else {
                $entities[$id]['level'] = 0;
                $entities[$id]['status_points'] = 0;
                $entities[$id]['ap_spend'] = 0;
            }
        }
        return $entities;


    }

    /**
     *
     * @return array
     */
    public function getFleetTechnologies($fleetId)
    {
        $this->_validateId($fleetId);

        $researches = $this->_gatherFleetTechnologyInformations($fleetId, 'research');
        $ships      = $this->_gatherFleetTechnologyInformations($fleetId, 'ship');
        $personell  = $this->_gatherFleetTechnologyInformations($fleetId, 'personell');

        $fleetTechnologies = array(
            'research'  => $researches,
            'ship'      => $ships,
            'personell' => $personell
        );

        return $fleetTechnologies;
    }
}
