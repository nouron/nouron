<?php
namespace Galaxy\Service;

use Zend\Session\Container;

class Gateway extends \Nouron\Service\AbstractService
{
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
     * @return ResultSet
     */
    public function getColonies()
    {
        return $this->getTable('colony')->fetchAll();
    }

    /**
     * @param integer $colonyId
     * @return \Galaxy\Entity\Colony
     */
    public function getColony($colonyId)
    {
        $this->_validateId($colonyId);
        return $this->getTable('colony')->getEntity($colonyId);
    }

    /**
     * Get all colonies from a user.
     *
     * @param  integer    $userId
     * @return ResultSet
     */
    public function getColoniesByUserId($userId)
    {
        $this->_validateId($userId);
        return $this->getTable('colony')->fetchAll('user_id = ' . $userId);
    }

    /**
     * @param numeric|\Galaxy\Entity\Colony $colony
     * @param numeric $userId
     * @return boolean
     */
    public function checkColonyOwner($colony, $userId)
    {
        if (is_numeric($colony)) {
            $colony = $this->getColony($colony);
        }
        if ($colony->getUserId() == $userId) {
            return true;
        } else {
            return false;
        }
    }

    /**
     *
     * @param  integer $userId
     * @return \Galaxy\Entity\Colony|null
     * @throws Exception if no main colony was found
     */
    public function getPrimeColony($userId)
    {
        $this->_validateId($userId);
        $colonies = $this->getColoniesByUserId((int) $userId);
        foreach ($colonies as $colony) {
            if ( $colony->is_primary || count($colonies) == 1) {
                if (!$colony->is_primary) {
                    $colony->is_primary = 1; /* set as prime colony*/
                    // TODO: $colony->save()
                }
                return $colony;
            }
        }

        // TODO: throw exception if no primary colony could be returned
    }

    /**
     * @deprecated since v0.2
     * @param unknown $userId
     */
    public function getMainColony($userId)
    {
        return $this->getPrimeColony();
    }

    /**
     *
     * @param integer $newColonyId
     */
    public function switchCurrentColony($newColonyId)
    {
        $session = new Container('activeIds');
        if ($this->checkColonyOwner($newColonyId, $session->userId)) {
            $session->colonyId = $newColonyId;
        }
    }

    /**
     *
     * @param  array $coords
     * @param string $objectType
     * @return ResultSet
     */
    public function getByCoordinates($objectType, array $coords)
    {
        //$config = $this->getConfig();
        $radius = round(100 / 2);

        $x1 = $coords[0] - $radius;
        $x2 = $coords[0] + $radius;
        $y1 = $coords[1] - $radius;
        $y2 = $coords[1] + $radius;

        switch (strtolower($objectType)) {
            case 'fleets':  $table = $this->getTable('fleet'); break;
            case 'colonies':$table = $this->getTable('colony');break;
            case 'objects': $table = $this->getTable('systemobject');break;
            default: return null;break;
        }

        $where = "x BETWEEN $x1 AND $x2 AND y BETWEEN $y1 AND $y2";

        return $table->fetchAll($where);
    }

    /**
     * Get a system object by id.
     *
     * @param  integer $systemId
     * @return \Galaxy\Entity\System
     */
    public function getSystem($systemId)
    {
        $this->_validateId($systemId);
        return $this->getTable('system')->getEntity($systemId);
    }

    /**
     * Get planetaries that surrounding a system
     *
     * @param   integer  $systemId
     * @return  \Galaxy\Entity\SystemObject
     */
    public function getSystemObjects($systemId)
    {
        $this->_validateId($systemId);
        $system = $this->getSystem($systemId);
        $coords = array($system->x, $system->y);

        return $this->getByCoordinates('objects', $coords);
    }

    /**
     *
     * @param $object
     */
    public function getSystemByPlanetary($object)
    {
        return $this->getSystemBySystemObject($object);
    }

    /**
     *
     * @param  \Galaxy\Entity\Colony | integer  $colony
     * @return \Galaxy\Entity\System
     */
    public function getSystemBySystemObject($object)
    {
        if (is_numeric($object)) {
            $object = $this->getSystemObject($object);
        }

        if (!($object instanceof \Galaxy\Entity\SystemObject)) {
            throw new \Exception('Not a valid colony.');
        }

        return $this->getSystemByObjectCoords(array($object['x'],$object['y']));
    }

//     /**
//      *
//      * @param \Galaxy\Entity\Colony | integer  $colony
//      * @return \Galaxy\Entity\System
//      */
//     public function getSystemByColony($colony)
//     {
//         if (is_numeric($colony)) {
//             $colony = $this->getColony($colony);
//         }

//         if (!($colony instanceof \Galaxy\Entity\Colony)) {
//             throw new \Galaxy\Entity\Exception('Not a valid colony.');
//         }

//         return $this->getSystemByObjectCoords($colony->getCoords());
//     }

//     /**
//      *
//      * @param  array  $object
//      * @return \Galaxy\Entity\System | null
//      */
//     public function getSystemByObjectCoords(array $coords)
//     {
//         $x = $coords[0];
//         $y = $coords[1];

//         $systems = $this->getSystems();
//         $config = $this->getConfig();
//         $radius = round($config->system->range / 2);

//         while ( $systems->valid() )
//         {
//             $sysCoords = $systems->getCoords();

//             $x1 = $sysCoords[0] - $radius;
//             $x2 = $sysCoords[0] + $radius;
//             $y1 = $sysCoords[1] - $radius;
//             $y2 = $sysCoords[1] + $radius;

//             if ($x >= $x1 && $x <= $x2 && $y >= $y1 && $y <= $y2) {
//                 return $systems->current();
//             }

//             $systems->next();
//         }
//     }

//     /**
//      *
//      * @param  integer $colonyId
//      * @return mixed
//      */
//     public function getSystemByColonyId($colonyId)
//     {
//         $colony = $this->getColony($colonyId);
//         $colCoords = $colony->getCoords();
//         $x = $colCoords[0];
//         $y = $colCoords[1];

//         $systems = $this->getSystems();
//         $config = $this->getConfig();
//         $radius = round($config->system->range / 2);

//         while ( $systems->valid() )
//         {
//             $coords = $systems->getCoords();

//             $x1 = $coords[0] - $radius;
//             $x2 = $coords[0] + $radius;
//             $y1 = $coords[1] - $radius;
//             $y2 = $coords[1] + $radius;

//             if ($x >= $x1 && $x <= $x2 && $y >= $y1 && $y <= $y2) {
//                 return $systems->current();
//             }

//             $systems->next();
//         }
//     }

    /**
     * Get the planetary object (planet, moon, asteroid field, etc. ) by its id.
     *
     * @param  integer $id
     * @return \Galaxy\Entity\System_Object
     */
    public function getSystemObject($id)
    {
        $this->_validateId($id);
        return $this->getTable('systemobject')->getEntity($id);
    }

    /**
     * Ermittelt den Planeten oder Mond anhand einer ColonyId
     *
     * @param  integer $colonyId
     * @return array
     */
    public function getSystemObjectByColonyId($colonyId)
    {
        $planetaryId = $this->getColony($colonyId)->system_object_id;
        return $this->getSystemObject($planetaryId);
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
        return $this->getByCoordinates('fleets', array($entity->getX(), $entity->getY()))->getArrayCopy();
    }

//     /**
//      * Get Distance between two coordinates.
//      *
//      * @todo this is not checked yet; what happens at grid borders or negative inputs?
//      *
//      * @param  array $coordsA
//      * @param  array $coordsB
//      * @return int
//      */
//     public function getDistance(array $coordsA, array $coordsB)
//     {
//         $a = $coordsA;
//         $b = $coordsB;
//         return ( abs($a[0] - $b[0]) + abs($a[1] - $b[1]) );
//     }

//     /**
//      * Get the distance from a to b in ticks.
//      * ATTENTION: It is assumed that one field in coords system takes 1 tick to travel,
//      * but that is a temporary convention and can be changed in future!
//      *
//      * @param   array   $coordsA   Source position
//      * @param   array   $coordsB   Target position
//      * @return  integer
//      */
//     public function getDistanceTicks(array $coordsA, array $coordsB)
//     {
//         return ( $this->getDistance($coordsA, $coordsB) + 1);
//     }

    /**
     * Get the path from A to B depending on the given speed.
     *
     * This is a modified version of the elegant form of the bresenham algorithm described on wikipedia:
     * @link http://de.wikipedia.org/wiki/Bresenham-Algorithmus
     *
     * The modification is that we have a given speed > 1 so that not every point
     * needs to be stored. Instead just one point per game tick is stored in the
     * returned path array.
     *
     * @param   array   $coordsA   Source position
     * @param   array   $coordsB   Target position
     * @param   integer $speed     Travel speed in fields per Tick
     * @return  array
     */
    public function getPath(array $coordsA, array $coordsB, $speed)
    {
        $tick = $this->getTick();

        $xstart = $coordsA[0];
        $ystart = $coordsA[1];
        $xend   = $coordsB[0];
        $yend   = $coordsB[1];

        /* Entfernung in beiden Dimensionen berechnen */
        $dx = $xend - $xstart;
        $dy = $yend - $ystart;

        /* Vorzeichen des Inkrements bestimmen */
        $incx = ($dx>0) ? 1 : (($dx<0) ? -1 : 0); // signum function
        $incy = ($dy>0) ? 1 : (($dy<0) ? -1 : 0);

        if( $dx < 0 ) $dx = -$dx;
        if( $dy < 0 ) $dy = -$dy;

        /* feststellen, welche Entfernung größer ist */
        if ( $dx > $dy ) {
            /* x ist schnelle Richtung */
            $pdx = $incx;
            $pdy = 0;    /* pd. ist Parallelschritt */
            $ddx = $incx;
            $ddy = $incy; /* dd. ist Diagonalschritt */
            $es  = $dy;
            $el  = $dx;   /* Fehlerschritte schnell, langsam */
        } else {
            /* y ist schnelle Richtung */
            $pdx = 0;
            $pdy = $incy; /* pd. ist Parallelschritt */
            $ddx = $incx;
            $ddy = $incy; /* dd. ist Diagonalschritt */
            $es  = $dx;
            $el  = $dy;   /* Fehlerschritte schnell, langsam */
        }

        /* Initialisierungen vor Schleifenbeginn */
        $x = $xstart;
        $y = $ystart;
        $err = $el/2;

        $path = array();
        $path[$tick] = $coordsA;  // first point in path is current position
        if (!isset($path[$tick][2])) {
            $path[$tick][2] = 0;
        }

        /* Pixel berechnen */
        for($t = 1; $t <= $el; ++$t) /* t zaehlt die Pixel, el ist auch Anzahl */
        {
            /* Aktualisierung Fehlerterm */
            $err -= $es;
            if( $err < 0 ) {
                /* Fehlerterm wieder positiv (>=0) machen */
                $err += $el;
                /* Schritt in langsame Richtung, Diagonalschritt */
                $x += $ddx;
                $y += $ddy;
            } else {
                /* Schritt in schnelle Richtung, Parallelschritt */
                $x += $pdx;
                $y += $pdy;
            }

            // wenn maximale Distanz pro Tick oder Zielpunkt erreicht setze Pfadpunkt:
            if ( ($t % $speed) == 0 || ($x == $xend && $y == $yend) ) {

                // neuen Pfadpunkt eintragen
                $path[++$tick] = array(0 => $x, 1 => $y, 2 => 0);
                if ( isset($coordsB[2]) && $x == $xend && $y == $yend) {
                    // wenn Colony-Slot gegeben und Zielpunkt erreicht setze Zielslot
                    $path[$tick++][2] = $coordsB[2];
                }
            }
        }

        return $path;
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

/*    /**
     * Get one specific technology from a colony specified by given compound primary key.
     * One technology from a colony - not more!
     * ATTENTION: This function allways return a colonytechnology object even if the
     * tech is not on the colony!
     *
     * @param  array $keys  The compound primary key in form: array('colony_id' => 1, 'tech_id' => 2)
     * @return \Galaxy\Entity\ColonyTechnology | array
     *
    public function getColonyTechnology($type, array $keys)
    {
        $entityTableName = 'colony'.$type;
        $entityIdName = $type.'_id';
        $table = $this->getTable($entityTableName);
        $tech = $table->fetchAll($keys);
        $result = $tech->current();
        if (empty($result)) {
            return array(
                'colony_id' => $keys['colony_id'],
                $entityIdName => $keys[$entityIdName],
                'count' => 0,
            );
        } else {
            return $result;
        }
    }

    public function getColonyResearch(array $keys)
    {
        return $this->getColonyTechnology('research', $keys);
    }

    public function getColonyPersonell(array $keys)
    {
        return $this->getColonyTechnology('personell', $keys);
    }

    public function getColonyBuilding(array $keys)
    {
        return $this->getColonyTechnology('building', $keys);
    }*/

    public function getColonyResource(array $keys)
    {
        $table = $this->getTable('colonyresource');
        $colonyres = $table->fetchAll($keys);
        $result = $colonyres->current();
        if (empty($result)) {
            return array(
                'colony_id' => $keys['colony_id'],
                'resource_id' => $keys['resource_id'],
                'amount' => 0,
            );
        } else {
            return $result->getArrayCopy();
        }
    }

    /**
     * Get the planetary object by its coords.
     * (Colony spot is ignored by comparison)
     *
     * @param  array $coords
     * @return \Galaxy\Entity\System_Object|null
     */
    public function getSystemObjectByCoords(array $coords)
    {
        $x = $coords[0];
        $y = $coords[1];
        $table = $this->getTable('systemobject');
        return $table->fetchAll("X = $x AND Y = $y")->current();
    }

    /**
     * Get a colony object by its coords
     *
     * @param  array $coords
     * @return \Galaxy\Entity\Colony|null
     */
    public function getColonyByCoords(array $coords)
    {
        $planetary = $this->getSystemObjectByCoords($coords);
        if (!empty($planetary)) {
            // get colos on the found planetary
            // (although it is a rowset only one row is possible!)
            $colos = $this->getColoniesBySystemObjectId($planetary->id);
            foreach ($colos as $colo) {
                // compare colony coords with given coords
                if (serialize(array($colo->x, $colo->y, $colo->spot) == serialize($coords))) {
                    return $colo;
                }
            }
        }
        // return null if no colony was found
        return null;
    }

    /**
     * Get all colonies from a planetary.
     *
     * @param  integer    $planetaryId
     * @return \Galaxy\Entity\Colonies
     */
    public function getColoniesBySystemObjectId($planetaryId)
    {
        $this->_validateId($planetaryId);
        $table = $this->getTable('colony');
        return $table->fetchAll("system_object_id = $planetaryId");
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
            $result = $table->fetchAll($where);
            #$cache->save($result, $cacheName, array('fleets', 'orders'));
        #}
        return $result;
    }
}