<?php
namespace Galaxy\Service;

class Gateway extends \Nouron\Service\Gateway
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
     * @return ResultSet
     */
    public function getColonies()
    {
        return $this->getTable('resource')->fetchAll();
    }

    /**
     * @return \Galaxy\Mapper\Colony
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
     * @return Galaxy_Model_Colonies
     */
    public function getColoniesByUserId($userId)
    {
        $this->_validateId($userId);
        return $this->getTable('colony')->fetchAll('user_id = ' . $userId);
    }

    /**
     *
     * @param  numeric $userId
     * @return Galaxy\Mapper\Colony|null
     * @throws Exception if no main colony was found
     */
    public function getPrimeColony($userId)
    {
        $this->_validateId($userId);
        $colonies = $this->getColoniesByUserId((int) $userId);
        foreach ($colonies as $colony) {
            if ( $colony['is_primary'] || count($colonies) == 1) {
                if (!$colony['is_primary']) {
                    $colony['is_primary'] = 1; /* set as prime colony*/
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
     * @return \Galaxy\Mapper\Colony|null
     */
    public function getCurrentColony()
    {
        if (!isset($_SESSION['colony'])) {
            $userId = 3; // TODO: get userId
            $_SESSION['colony'] = $this->getPrimeColony($userId);
        }
        return $_SESSION['colony'];
    }

    /**
     *
     * @param unknown $newColonyId
     */
    public function switchCurrentColony($newColonyId)
    {
        $_SESSION['colony'] = $this->getColony((int) $newColonyId);
    }

//     /**
//      *
//      * @return Zend_Config_Ini
//      */
//     public function getConfig()
//     {
//         return new Zend_Config_Ini(APPLICATION_PATH . '/modules/galaxy/configs/module.ini', APPLICATION_ENV);
//     }

    /**
     * get all colonies in a system system
     * @TODO this function is very similar to $this->getFleetsBySystemCoordinates,
     *       maybe its possible to merge this to function and parameterize it?
     *
     * @param  array $coords
     * @return Galaxy_Model_Colonies
     */
    public function getColoniesBySystemCoordinates(array $coords)
    {
        //$config = $this->getConfig();
        $radius = round(100 / 2);

        $x1 = $coords[0] - $radius;
        $x2 = $coords[0] + $radius;
        $y1 = $coords[1] - $radius;
        $y2 = $coords[1] + $radius;

        $table = $this->getTable('colony');
        return $table->fetchAll("x BETWEEN $x1 AND $x2 AND y BETWEEN $y1 AND $y2");
    }

    /**
     * Get a system object by id.
     *
     * @param  integer $systemId
     * @return Galaxy_Model_System
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
     * @param   string   $order      OPTIONAL: sql order string
     * @return  Galaxy_Model_System_Objects
     */
    public function getSystemObjects($systemId, $order = null, $systemRange = null)
    {
        $this->_validateId($systemId);

        if (empty($systemRange)) {
            $systemRange = 100; // TODO: get value from config instead of hardcoded value
        }

        $radius = round($systemRange / 2);
        $system = $this->getSystem($systemId);

        $x1 = $system['x'] - $radius;
        $x2 = $system['x'] + $radius;
        $y1 = $system['y'] - $radius;
        $y2 = $system['y'] + $radius;

        $plntrsView = $this->getTable('systemobject');
        $where  = "x BETWEEN $x1 AND $x2 AND y BETWEEN $y1 AND $y2";

        return $plntrsView->fetchAll($where, $order);
    }

//     /**
//      *
//      * @param $object
//      */
//     public function getSystemByPlanetary($object)
//     {
//         return $this->getSystemBySystemObject($object);
//     }

//     /**
//      *
//      * @param Galaxy_Model_Colony | integer  $colony
//      * @return Galaxy_Model_System
//      */
//     public function getSystemBySystemObject($object)
//     {
//         if (is_numeric($object)) {
//             $object = $this->getSystemObject($object);
//         }

//         if (!($object instanceof Galaxy_Model_System_Object)) {
//             throw new Galaxy_Model_Exception('Not a valid colony.');
//         }

//         return $this->getSystemByObjectCoords($object->getCoords());
//     }

//     /**
//      *
//      * @param Galaxy_Model_Colony | integer  $colony
//      * @return Galaxy_Model_System
//      */
//     public function getSystemByColony($colony)
//     {
//         if (is_numeric($colony)) {
//             $colony = $this->getColony($colony);
//         }

//         if (!($colony instanceof Galaxy_Model_Colony)) {
//             throw new Galaxy_Model_Exception('Not a valid colony.');
//         }

//         return $this->getSystemByObjectCoords($colony->getCoords());
//     }

//     /**
//      *
//      * @param  array  $object
//      * @return Galaxy_Model_System | null
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

//     /**
//      * Get the planetary object (planet, moon, asteroid field, etc. ) by its id.
//      *
//      * @param  integer $id
//      * @return Galaxy_Model_System_Object
//      */
//     public function getSystemObject($id)
//     {
//         $dbView = $this->getDbView('system_objects');
//         $result = $dbView->find($id)->current();
//         return new Galaxy_Model_System_Object($result, $this);
//     }

//     /**
//      * Ermittelt den Planeten oder Mond anhand einer ColonyId
//      *
//      * @param  integer$colonyId
//      * @return array
//      */
//     public function getSystemObjectByColonyId($colonyId)
//     {
//         $planetaryId = $this->getColony($colonyId)->nPlanetary;
//         return $this->getSystemObject($planetaryId);
//     }

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

//     /**
//      * Get the path from A to B depending on the given speed.
//      *
//      * This is a modified version of the elegant form of the bresenham algorithm described on wikipedia:
//      * @link http://de.wikipedia.org/wiki/Bresenham-Algorithmus
//      *
//      * The modification is that we have a given speed > 1 so that not every point
//      * needs to be stored. Instead just one point per game tick is stored in the
//      * returned path array.
//      *
//      * @param   array   $coordsA   Source position
//      * @param   array   $coordsB   Target position
//      * @param   integer $speed     Travel speed in fields per Tick
//      * @return  array
//      */
//     public function getPath(array $coordsA, array $coordsB, $speed)
//     {
//         $tick = Zend_Registry::get('Tick');

//         if ( !is_numeric($tick) ) {
//             throw new Galaxy_Model_Exception('Invalid tick number given.');
//         }

//         $xstart = $coordsA[0];
//         $ystart = $coordsA[1];
//         $xend   = $coordsB[0];
//         $yend   = $coordsB[1];

//         /* Entfernung in beiden Dimensionen berechnen */
//         $dx = $xend - $xstart;
//         $dy = $yend - $ystart;

//         /* Vorzeichen des Inkrements bestimmen */
//         $incx = ($dx>0) ? 1 : (($dx<0) ? -1 : 0); // signum function
//         $incy = ($dy>0) ? 1 : (($dy<0) ? -1 : 0);

//         if( $dx < 0 ) $dx = -$dx;
//         if( $dy < 0 ) $dy = -$dy;

//         /* feststellen, welche Entfernung größer ist */
//         if ( $dx > $dy ) {
//             /* x ist schnelle Richtung */
//             $pdx = $incx;
//             $pdy = 0;    /* pd. ist Parallelschritt */
//             $ddx = $incx;
//             $ddy = $incy; /* dd. ist Diagonalschritt */
//             $es  = $dy;
//             $el  = $dx;   /* Fehlerschritte schnell, langsam */
//         } else {
//             /* y ist schnelle Richtung */
//             $pdx = 0;
//             $pdy = $incy; /* pd. ist Parallelschritt */
//             $ddx = $incx;
//             $ddy = $incy; /* dd. ist Diagonalschritt */
//             $es  = $dx;
//             $el  = $dy;   /* Fehlerschritte schnell, langsam */
//         }

//         /* Initialisierungen vor Schleifenbeginn */
//         $x = $xstart;
//         $y = $ystart;
//         $err = $el/2;

//         $path = array();
//         $path[$tick] = $coordsA;  // first point in path is current position
//         if (!isset($path[$tick][2])) {
//             $path[$tick][2] = 0;
//         }

//         /* Pixel berechnen */
//         for($t = 1; $t <= $el; ++$t) /* t zaehlt die Pixel, el ist auch Anzahl */
//         {
//             /* Aktualisierung Fehlerterm */
//             $err -= $es;
//             if( $err < 0 ) {
//                 /* Fehlerterm wieder positiv (>=0) machen */
//                 $err += $el;
//                 /* Schritt in langsame Richtung, Diagonalschritt */
//                 $x += $ddx;
//                 $y += $ddy;
//             } else {
//                 /* Schritt in schnelle Richtung, Parallelschritt */
//                 $x += $pdx;
//                 $y += $pdy;
//             }

//             // wenn maximale Distanz pro Tick oder Zielpunkt erreicht setze Pfadpunkt:
//             if ( ($t % $speed) == 0 || ($x == $xend && $y == $yend) ) {

//                 // neuen Pfadpunkt eintragen
//                 $path[++$tick] = array(0 => $x, 1 => $y, 2 => 0);
//                 if ( isset($coordsB[2]) && $x == $xend && $y == $yend) {
//                     // wenn Colony-Slot gegeben und Zielpunkt erreicht setze Zielslot
//                     $path[$tick++][2] = $coordsB[2];
//                 }
//             }
//         }

//         //        foreach ($path as $tick => $coords){
//         //            print($tick.': '); print_r($coords);print('<br />');
//         //        }
//         //        exit;

//         return $path;
//     }

//     /**
//      * Add a fleet Order.
//      *
//      * @param  string   $order
//      * @param  integer|Galaxy_Model_Fleet
//      *              $fleet                The Id of a fleet or a fleet Object
//      * @param  integer|array|string|Galaxy_Model_Fleet|Galaxy_Model_Fleet
//      *              $destination          The Id or Coords of a colony or fleet
//      * @return boolean
//      * @throws Galaxy_Model_Exception
//      */
//     public function addOrder($order, $fleet, $destination, $additionalData = null)
//     {
//         // check the parameter '$fleet':
//         if ( is_numeric($fleet) ) {
//             $fleetId = (int) $fleet;
//             $fleet = $this->getFleet($fleetId);
//         }

//         if ( !($fleet instanceOf Galaxy_Model_Fleet) ) {
//             throw new Galaxy_Model_Exception('The Object is not a valid Fleet!');
//         }

//         return $fleet->addOrder($order, $destination, $additionalData);
//     }

//     /**
//      * Transfer an amount of technology from colony to fleet.
//      * (Vice versa when amount is negative!)
//      *
//      * @param  int|object   $colony
//      * @param  int|object   $fleet
//      * @param  integer      $techId
//      * @param  integer      $amount
//      * @param  boolean      $isCargo       OPTIONAL set true if use fleet cargo (not fleet itself)
//      * @param  boolean      $isTradeOffer  OPTIONAL set true to fullfill an existing trade offer
//      *
//      * @return int          Count of transfered Technologies
//      */
//     public function transferTechnology($colony, $fleet, $techId, $amount, $isCargo = false, $isTradeOffer = false)
//     {
//         if (is_numeric($colony)) {
//             $colony = $this->getColony($colony);
//         }

//         if (is_numeric($fleet)) {
//             $fleet = $this->getFleet($fleet);
//         }

//         if (serialize($colony->getCoords()) == serialize($fleet->getCoords())) {

//             if ( !$isTradeOffer ) {
//                 $techtreeGw   = new Techtree_Model_Gateway();
//                 $techOnColony = $techtreeGw->getPossession(array('nColony' => $colony->nId, 'nTechnology' => $techId));
//             } else {
//                 $tradeGw      = new Trade_Model_Gateway();
//                 $techOnColony = $tradeGw->getTechnologyOffer($colony->nId, $techId);
//                 if ( $techOnColony == null) {
//                     return 0;
//                 }
//             }

//             $techInFleet  = $this->getFleetTechnology(array('nFleet' => $fleet->nId, 'nTechnology' => $techId, 'bIsCargo' => $isCargo));

//             if ($amount >= 0 ) {
//                 // check if there are enough techs on the colony:
//                 if ($amount > $techOnColony->nCount) {
//                     // only remove the count of techs that really exists on the colony;
//                     $amount = $techOnColony->nCount;
//                 }
//             } else {
//                 // check if there are enough techs in the fleet:
//                 if ($amount < -$techInFleet->nCount) {
//                     // only remove the count of techs that really exists in the fleet:
//                     $amount = -$techInFleet->nCount;
//                 }
//             }

//             try {
//                 $db = $this->getDbTable('fleets')->getAdapter();
//                 $db->beginTransaction();
//                 $techOnColony->nCount = $techOnColony->nCount - $amount;
//                 $techOnColony->save();
//                 $techInFleet->nCount = $techInFleet->nCount + $amount;
//                 $techInFleet->save();

//                 $db->commit();

//             } catch (Exception $e) {
//                 $db->rollBack();
//                 throw new Galaxy_Model_Exception( $e->getMessage() );
//             }

//             return abs($amount);
//         }
//     }

//     /**
//      * Transfer an amount of resources from colony to fleet.
//      * (Vice versa when amount is negative!)
//      *
//      * @param  int|object  $colony
//      * @param  int|object  $fleet
//      * @param  integer     $resId
//      * @param  integer     $amount
//      * @param  boolean     $isTradeOffer  OPTIONAL set true to fullfill an existing trade offer
//      * @return int    Count of transfered res
//      */
//     public function transferResource($colony, $fleet, $resId, $amount, $isTradeOffer = false)
//     {
//         if (is_numeric($colony)) {
//             $colony = $this->getColony($colony);
//         }

//         if (is_numeric($fleet)) {
//             $fleet = $this->getFleet($fleet);
//         }

//         if (serialize($colony->getCoords()) == serialize($fleet->getCoords())) {

//             if ( !$isTradeOffer ) {
//                 $resGw       = new Resources_Model_Gateway();
//                 $resOnColony = $resGw->getColonyResource(array('nColony' => $colony->nId, 'nResource' => $resId));
//             } else {
//                 $tradeGw     = new Trade_Model_Gateway();
//                 $resOnColony = $tradeGw->getResourceOffer($colony->nId, $resId);
//                 if ( $resOnColony == null) {
//                     return 0;
//                 }
//             }
//             $resInFleet  = $this->getFleetResource(array('nFleet' => $fleet->nId, 'nResource' => $resId));

//             if ($amount >= 0 ) {
//                 // check if there are enough res on the colony:
//                 if ($amount > $resOnColony->nAmount) {
//                     // only remove the count of res that really exists in the fleet:
//                     $amount = $resOnColony->nAmount;
//                 }
//             } else {
//                 // check if there are enough res in the fleet:
//                 if ($amount < -$resInFleet->nAmount) {
//                     // only remove the count of res that really exists in the fleet:
//                     $amount = -$resInFleet->nAmount;
//                 }
//             }

//             try {
//                 $db = $this->getDbTable('fleets')->getAdapter();
//                 $db->beginTransaction();

//                 $resOnColony->nAmount = $resOnColony->nAmount - $amount;
//                 $resOnColony->save();
//                 $resInFleet->nAmount = $resInFleet->nAmount + $amount;
//                 $resInFleet->save();

//                 $db->commit();

//             } catch (Exception $e) {
//                 $db->rollBack();
//                 throw new Galaxy_Model_Exception( $e->getMessage() );
//             }

//             return abs($amount);
//         }
//     }

//     /**
//      * Get one specific technology from a fleet specified by given compound primary key.
//      * One technology from a fleet - not more!
//      * ATTENTION: This function allways return a fleets_technology object even if the
//      * tech is not in the fleet!
//      * @TODO: proove this!
//      *
//      * @param  array $keys  The compound primary key in form: array('nFleet' => 1, 'nTechnology' => 2)
//      * @return Galaxy_Model_Fleets_Technology
//      */
//     public function getFleetTechnology(array $keys)
//     {
//         if (!isset($keys['nFleet']) || !isset($keys['nTechnology'])) {
//             throw new Techtree_Model_Exception('Not a valid compound primary key.');
//         }

//         $table = $this->getDbView('Fleets_Technologies');
//         foreach ($table->info('primary') as $id) {
//             $val = $keys[$id];
//             $this->_validateId($val);
//             $sql[] = "$id = $val";
//         }
//         $result = $table->fetchAll($sql);
//         if ($result->valid()) {
//             $row = $result->current();
//         } else {
//             $row = $keys;
//         }
//         return new Galaxy_Model_Fleets_Technology($row, $this);
//     }

//     /**
//      * Get all ships and other technologies from a fleet.
//      *
//      * @param $where
//      * @return Galaxy_Model_Fleets_Technologies
//      */
//     public function getFleetTechnologies($where)
//     {
//         $dbTable = $this->getDbView('Fleets_Technologies');
//         $rowset = $dbTable->fetchAll($where);
//         $result = new Galaxy_Model_Fleets_Technologies($rowset, $this);
//         return $result;
//     }

//     /**
//      * Get one specific resource from a fleet specified by given compound primary key.
//      * One resource from a fleet - not more!
//      *
//      * @param  array $keys  The compound primary key in form: array('nFleet' => 1, 'nResource' => 2)
//      * @return Galaxy_Model_Fleets_Resource
//      */
//     public function getFleetResource(array $keys)
//     {
//         if (!isset($keys['nFleet']) || !isset($keys['nResource'])) {
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
//         return new Galaxy_Model_Fleets_Resource($row, $this);
//     }

//     /**
//      *
//      *
//      * @param  string|array|object $fleets
//      * @param  boolean $past   Include orders in the past?
//      * @return Galaxy_Model_Fleets_Orders
//      */
//     public function getFleetsOrders($fleets, $past = false)
//     {
//         if ($fleets instanceof Galaxy_Model_Fleets) {
//             $fleets = $fleets->toArray();
//         }

//         if (is_array($fleets)) {
//             $fleets = implode(',', array_keys($fleets));
//         }

//         if (!is_string($fleets)) {
//             throw new Exception('fleet must be object, array or string');
//         }

//         if (empty($fleets)) {
//             return new Galaxy_Model_Fleets_Orders(array(), $this);
//         }

//         $table = $this->getDbTable('Fleets_Orders');
//         if (!$past) {
//             $tick = Zend_Registry::get('Tick');
//             $orders = $table->fetchAll("nFleet IN ($fleets) AND nTick >= $tick");
//         } else {
//             $orders = $table->fetchAll("nFleet IN ($fleets)");
//         }

//         return new Galaxy_Model_Fleets_Orders($orders, $this);
//     }

//     /**
//      * Get the planetary object by its coords.
//      * (Colony spot is ignored by comparison)
//      *
//      * @param  array $coords
//      * @return Galaxy_Model_System_Object|null
//      */
//     public function getSystemObjectByCoords(array $coords)
//     {
//         $x = $coords[0];
//         $y = $coords[1];
//         $view = $this->getDbView('system_objects');
//         $row = $view->fetchRow("X = $x AND Y = $y");
//         if (!empty($row)) {
//             return new Galaxy_Model_System_Object($row, $this);
//         }
//         // if none found:
//         return null;
//     }

//     /**
//      * Get a colony object by its coords
//      *
//      * @param  array $coords
//      * @return Galaxy_Model_Colony|null
//      */
//     public function getColonyByCoords(array $coords)
//     {
//         $planetary = $this->getSystemObjectByCoords($coords);
//         if (!empty($planetary)) {
//             // get colos on the found planetary
//             // (although it is a rowset only one row is possible!)
//             $colos = $this->getColoniesBySystem_ObjectId($planetary->nId);
//             while ($colos->valid()) {
//                 // compare colony coords with given coords
//                 if (serialize($colos->getCoords()) == serialize($coords)) {
//                     return new Galaxy_Model_Colony($colos->current()->toArray(), $this);
//                 }
//                 $colos->next();
//             }
//         }
//         // return null if no colony was found
//         return null;
//     }

//     /**
//      *
//      * @param  integer $colonyId
//      * @return Techtree_Model_Orders
//      */
//     public function getOrders($where = null, $order = null, $count = null, $offset = null)
//     {
//         $cache = Zend_Registry::get('cache');

//         $cacheName = 'fleet_orders_' . md5(serialize($where).serialize($order).$count.$offset);
//         if (!($result = $cache->load($cacheName))) {
//             $dbTable = $this->getDbTable('Fleets_Orders');
//             $rowset = $dbTable->fetchAll($where, $order, $count, $offset);

//             $result = new Galaxy_Model_Fleets_Orders($rowset, $this);
//             $cache->save($result, $cacheName, array('fleets', 'orders'));
//         }
//         return $result;
//     }

//     /**
//      *
//      * @param numeric $userId
//      * @param numeric $sinceTick
//      */
//     public function getProcessedOrders($userId, $sinceTick = null)
//     {
//         $this->_validateId($userId);

//         $lastTick = Zend_Registry::get('Tick') - 1;

//         if ( !is_numeric($sinceTick) ) {
//             $sinceTick = $lastTick;
//         }

//         return $this->getOrders("nTick >= $sinceTick AND nTick <= $lastTick AND sOrder <> 'move' AND bProcessed = 1");
//     }

//     /**
//      * Find all processed orders and notify innn and eventDispatcher about them.
//      * Set bNotified to true to avoid a double notification.
//      *
//      * - set innn events
//      * - set event dispatcher events
//      *
//      * @param numeric $userId
//      */
//     public function notifyAboutProcessedOrders($userId)
//     {
//         $this->_validateId($userId);
//         $tick = Zend_Registry::get('Tick');
//         $innnGw = new Innn_Model_Gateway();
//         $orders = $this->getProcessedOrders($userId);
//         $cache = Zend_Registry::get('cache');
//         while ( $orders->valid() ) {

//             if ( $orders->bNotified == 0 ) {

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
//                         'nTick' => $tick,
//                         'sEvent' => $event,
//                         'sParameters' => serialize(array('nColony' => $orderData['nColony']))
//                 );

//                 $innnEvent = $innnGw->createEvent($data);
//                 $innnEvent->save();

//                 // notify event dispatcher:
//                 $event = new sfEvent($this, $event );
//                 $dispatcher = Zend_Registry::get('eventDispatcher');
//                 $dispatcher->notify($event);

//                 // mark order as notified
//                 $order->bNotified = 1;
//                 $order->save();

//                 // clear cache
//                 $cache->clean(Zend_Cache::CLEANING_MODE_MATCHING_TAG, array('fleets','orders'));
//             }
//             $orders->next();
//         }
//     }
}