<?php
namespace Galaxy\Service;

use Zend\Session\Container;

class Gateway extends \Core\Service\AbstractService
{
    public function __construct($tick, array $tables, array $services = array(), array $config)
    {
        parent::__construct($tick, $tables, $services);
        $this->config = $config;
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
     *
     * @param  array $coords
     * @param string $objectType
     * @return ResultSet
     */
    public function getByCoordinates($objectType, array $coords)
    {
        //$config = $this->getConfig();
        $radius = round(50 / 2);

        $x1 = $coords[0] - $radius;
        $x2 = $coords[0] + $radius;
        $y1 = $coords[1] - $radius;
        $y2 = $coords[1] + $radius;

        switch (strtolower($objectType)) {
            case 'fleets':
                $table = $this->getTable('fleet');
                break;
            case 'colonies':
                $table = $this->getTable('colony');
                break;
            case 'objects':
                $table = $this->getTable('systemobject');
                break;
            default:
                return null;
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
        $coords = array($system->getX(), $system->getY());

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
     * @param  \Galaxy\Entity\Colony | integer  $colony object or id
     * @return \Galaxy\Entity\System If system was found else false
     * @throws \Core\Service\Exception If not a valid system object given
     */
    public function getSystemBySystemObject($object)
    {
        if (is_numeric($object)) {
            $object = $this->getSystemObject($object);
        }

        if (!($object instanceof \Galaxy\Entity\SystemObject)) {
            throw new \Core\Service\Exception('Not a valid system object.');
        }

        return $this->getSystemByObjectCoords(array($object->getX(), $object->getY()));
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

     /**
      *
      * @param  array  $coords with x and y coordinates
      * @return \Galaxy\Entity\System | false
      */
     public function getSystemByObjectCoords(array $coords)
     {
         $x = $coords[0];
         $y = $coords[1];

         $systems = $this->getSystems();
         $config = $this->config['system_view_config'];
         $radius = round($config['range'] / 2);

         foreach ($systems as $system) {
             $x1 = $system->getX() - $radius;
             $x2 = $system->getX() + $radius;
             $y1 = $system->getY() - $radius;
             $y2 = $system->getY() + $radius;

             if ($x >= $x1 && $x <= $x2 && $y >= $y1 && $y <= $y2) {
                 return $system;
             }
         }
         return false;
     }

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
     * @return \Galaxy\Entity\SystemObject
     * @throws \Core\Service\Exception If invalid id
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
     * @return \Galaxy\Entity\SystemObject | false
     * @throws \Core\Service\Exception If invalid id
     */
    public function getSystemObjectByColonyId($colonyId)
    {
        $this->_validateId($colonyId);
        $colony = $this->getTable('colony')->getEntity($colonyId);
        if ($colony) {
            $planetaryId = $colony->getSystemObjectId();
            return $this->getSystemObject($planetaryId);
        } else {
            return false;
        }
    }

     /**
      * Get Distance between two coordinates.
      *
      * @todo this is not checked yet; what happens at grid borders or negative inputs?
      *
      * @param  array $coordsA
      * @param  array $coordsB
      * @return int
      */
     public function getDistance(array $coordsA, array $coordsB)
     {
         $a = $coordsA;
         $b = $coordsB;
         return ( abs($a[0] - $b[0]) + abs($a[1] - $b[1]) );
     }

     /**
      * Get the distance from a to b in ticks.
      * ATTENTION: It is assumed that one field in coords system takes 1 tick to travel,
      * but that is a temporary convention and can be changed in future!
      *
      * @param   array   $coordsA   Source position
      * @param   array   $coordsB   Target position
      * @return  integer
      */
     public function getDistanceTicks(array $coordsA, array $coordsB)
     {
         return ( $this->getDistance($coordsA, $coordsB) + 1);
     }

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

#    public function getColonyResource(array $keys)
#    {
#        $table = $this->getTable('colonyresource');
#        $colonyres = $table->fetchAll($keys);
#        $result = $colonyres->current();
#        if (empty($result)) {
#            return array(
#                'colony_id' => $keys['colony_id'],
#                'resource_id' => $keys['resource_id'],
#                'amount' => 0,
#            );
#        } else {
#            return $result->getArrayCopy();
#        }
#    }

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
        if (!is_numeric($x) || !is_numeric($y)) {
            throw new \Core\Service\Exception('Invalid Coordinates.');
        }
        $table = $this->getTable('systemobject');
        return $table->fetchAll("X = $x AND Y = $y")->current();
    }

#    /**
#     *
#     * @param string $where
#     * @param string $order
#     * @param string $count
#     * @param string $offset
#     * @return ResultSet
#     */
#    public function getOrders($where = null, $order = null, $count = null, $offset = null)
#    {
#        #$cache = Zend_Registry::get('cache');
#        #$cacheName = 'fleet_orders_' . md5(serialize($where).serialize($order).$count.$offset);
#        #if (!($result = $cache->load($cacheName))) {
#            $table = $this->getTable('fleetorder');
#            $result = $table->fetchAll($where);
#            #$cache->save($result, $cacheName, array('fleets', 'orders'));
#        #}
#        return $result;
#    }
}