<?php

namespace Nouron\Service;


/**
 * @category   Nouron
 * @package    Nouron_Tick
 */
class Tick
{
    /**
     * @var integer
     */
    protected $tick = null;

    /**
     *
     */
    public function __toString()
    {
        return (string) $this->tick;
    }

    /**
     *
     * @param string $tick
     */
    public function __construct($config, $tick = null)
    {
        $this->config = $config;

        if (is_numeric($tick) && $tick > 0) {
            $this->tick = (int) $tick;
        } else {
            $this->tick = $this->getTickFromTimestamp( time() );
        }
    }

    /**
     *
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     *
     * @param unknown $config
     */
    public function setConfig($config)
    {
        $this->config = $config;
    }

    /**
     * @return boolean
     */
    public function calculationIsRunning()
    {
        $time = time();
        $config = $this->getConfig();
        $calc_begin = (int) $config['calculation']['start'];
        $calc_end   = (int) $config['calculation']['end'];
        // @TODO Unterscheidung Sommer/Winter funzt noch nicht
        if ($time >= mktime($calc_begin) && $time < mktime($calc_end)) {
            return true;
        } else {
            return false;
        }
    }

//     /**
//      *
//      * @param Zenddb_Statement $stmt
//      */
//     public function printResult(Zenddb_Statement $stmt)
//     {
//         if ($stmt->errorCode() != '00000') {
//             print('<span style="color:#900">Error!</span><br />');
//             print_r($stmt->errorInfo());
//             print('<br />');
//         } else {
//             print('<span style="color:#090">Ok</span> ('.$stmt->rowCount().')<br />');
//         }
//     }

    /**
     * set tick count to the given value
     *
     * @param  $tick
     */
    public function setTickCount($tick)
    {
        if (is_numeric($tick) && $tick > 0) {
            $this->tick = (int) $tick;
        }
    }

    /**
     * get tick count
     *
     * @return integer
     */
    public function getTickCount()
    {
        return $this->tick;
    }

    /**
     * get Tick from given timestamp value
     *
     * Example calculation of tick (tick is calculated from 3am to 4am):
     *
     * (unix timestamp - 4h) / 24 hours = days since 1970-01-01 = current tick
     *
     * @param date/time/datetime $date
     */
    public function getTickFromTimestamp($time)
    {
        $time = time();
        $config = $this->getConfig();
        $calc_begin = (int) $config['calculation']['start'];
        $calc_end   = (int) $config['calculation']['end'];

        $tick = (int) floor( ( $time - 60 * 60 * $calc_end ) / (60 * 60 * 24));
        return $tick;
    }

//     /**
//      * @return Zenddb_Statement
//      */
//     function removeOldMessages()
//     {
//         $tick = $this->tick - 60; // nachrichten die älter als 60 ticks sind
//         return $this->db->query("DELETE FROM t_innn_messages
//          WHERE bRead = 1 AND bArchived = 0 AND nTick < $tick");
//     }

//     /**
//      * @return Zenddb_Statement
//      */
//     function removeOldEvents()
//     {
//         $tick = $this->tick - 30; // events die älter als 30 ticks sind
//         return $this->db->query("DELETE FROM t_innn_events WHERE nTick < $tick");
//     }

//     /**
//      *
//      * @return Zenddb_Statement
//      */
//     function generateResources()
//     {
//         return $this->db->query(
//             "UPDATE
//               t_res_possessions AS poss,
//               v_res_production_per_tick AS prod
//             SET
//               poss.nAmount = poss.nAmount + prod.nDelta
//             WHERE
//               poss.nColony = prod.nColony
//             AND
//               poss.nResource = prod.nResource;"
//         );
//     }

//     /**
//      * ageing the technologies:
//      * 1.step: set age + 1
//      * 2.step: level down techs where age > decay
//      * 3.step: delete techs where count <= 0
//      *
//      * @return ???
//      */
//     function ageingTechnologies()
//     {
//         // set age + 1
//         $query =
//            "UPDATE
//               t_tech_possessions
//             SET
//               nAge = nAge + 1;";
//         $stmt = $this->db->query($query);
//         print('- ageing: ');
//         $this->printResult($stmt);

//         // leveldown where age > decay
//         $query =
//            "UPDATE
//               t_tech_possessions AS poss,
//               t_tech_technologies AS tech
//             SET
//               poss.nCount = poss.nCount - 1,
//               poss.nAge   = 0
//             WHERE
//               poss.nTechnology = tech.nId
//             AND
//               poss.nAge > tech.nDecay;";
//         //print("<small>$query</small><br />");
//         $stmt = $this->db->query($query);
//         print('- leveldown: ');
//         $this->printResult($stmt);

//         // delete where count <= 0
//         $query = "DELETE FROM t_tech_possessions WHERE nCount <= 0;";
//         //print("<small>$query</small><br />");
//         $stmt = $this->db->query($query);

//         print('- deleting: ');
//         $this->printResult($stmt);
//     }

//     /**
//      *
//      * @param  string $orderType
//      * @param  integer $tick
//      * @return Zenddb_Statement
//      */
//     function processTechOrders($orderType)
//     {
//         //    $types = array('add','sub','renew');
//         //    in_array($orderType, $types);

//         switch ($orderType) {
//             case 'add':
//                 // wenn ein tech einer 'add'-Order hat aber noch nicht in der possessions-Tabelle steht,
//                 // muss zunächst dieser Eintrag hergestellt werden

//                 // erstmal abfragen welche Techs denn noch fehlen
//                 // (das sind jene die noch nCount = Null haben)
//                 $query = "
//                     SELECT *
//                     FROM v_tech_orders
//                     WHERE ISNULL(nCount)
//                     AND ISNULL(nAge)
//                     AND nTick = $this->tick";

//                 $stmt = $this->db->query($query);
//                 $rows = $stmt->fetchAll();

//                 if (count($rows) > 0) {
//                     // füge die fehlenden Techs in die possessions-Table ein (mit count=0!)
//                     $query = "
//                         INSERT INTO t_tech_possessions (nTechnology, nColony, nCount, nAge)
//                         VALUES ";
//                     foreach ($rows as $row) {
//                         $query .= " ({$row['nTechnology']}, {$row['nColony']}, 0, 0), ";
//                     }
//                     $query = rtrim($query, ", ");// letztes komma abschneiden, dann query ausführen
//                     $this->db->query( $query );
//                 }
//                 $count = 'IFNULL(nCount,0) + 1';
//                 break;
//             case 'sub':
//                 $count = 'IFNULL(nCount,1) - 1';
//                 break;
//             default: // renew
//                 break;
//         }

//         // alle Techs die für den aktuellen Tick eine FINAL Order haben
//         // werden jetzt in der possessions-Tabelle erhöht
//         $query =
//            "UPDATE
//                 v_tech_orders
//             SET
//                 nCount = $count,
//                 nAge = 0
//             WHERE
//                 nTick = $this->tick
//             AND sAction = '$orderType'
//             AND bFinalStep = 1;";

//         // alte Variante ohne View:
// //        $query =
// //           "UPDATE
// //              t_tech_possessions AS poss,
// //              t_tech_orders AS orders
// //            SET
// //                poss.nCount = $count,
// //                poss.nAge = 0
// //            WHERE
// //                poss.nColony = orders.nColony
// //            AND poss.nTechnology = orders.nTechnology
// //            AND orders.nTick   = $this->tick
// //            AND orders.sAction = '$orderType';";

//         //print("<small>$query</small>");

//         return $this->db->query($query);
//     }

//     /**
//      *
//      * @return Zenddb_Statement
//      */
//     function processFleetOrders($orderType)
//     {
//         $stmt = $this->db->query(
//             "UPDATE
//                 t_glx_fleets as fleets,
//                 t_glx_fleet_orders as orders
//             SET
//               fleets.sCoordinates = orders.sCoordinates
//             WHERE
//               fleets.nId = orders.nFleet
//             AND
//               orders.nTick = $this->tick;"
//         );

//         switch (strtolower($orderType)) {
//             case 'move':  return $stmt; break;
//             case 'trade': return $this->_processFleetTradeOrders(); break;
//             //case 'attack':return $stmt; break;
//         }
//     }

//     private function _processFleetTradeOrders()
//     {
//         $tradeGw  = new Trade_Model_Gateway();
//         $galaxyGw = new Galaxy_Model_Gateway();
//         $orders = $galaxyGw->getFleetsOrders("sOrder = 'trade'");

//         $tradeGw->processFleetTradeOrders($orders);

//         // trade
//         return $this->db->query(
//             "UPDATE
//                 t_glx_fleets as fleets,
//                 t_glx_fleet_orders as orders
//             SET
//               fleets.sCoordinates = orders.sCoordinates
//             WHERE
//               fleets.nId = orders.nFleet
//             AND
//               orders.nTick = $this->tick;"
//         );
//     }

//     function calculateSupply()
//     {
// //        //verfügbares Supply
// //        $query = "SELECT *
// //          FROM v_tech_possessions
// //          WHERE (
// //              sTechnology = 'housing_complex' OR
// //              sTechnology = 'command_center'
// //          )
// //        ";
// //        $result = $db->fetchAll($query);
// //        $max_supply = array();
// //
// //        foreach ( $result as $key => $s )
// //        {
// //            if (!isset($max_supply[$s['nUser']]))
// //            {
// //                $max_supply[$s['nUser']] = 0;
// //            }
// //            // Für jedes Kommandozentrum und Wohngebäude +10 Supplyplatz pro Gebäudestufe
// //            // @TODO: Wert anpassen
// //            $max_supply[$s['nUser']] += $s['nCount'] * 100;
// //        }
// //        print('== max_supply: ==<br/>');
// //        Zend_Debug::dump($max_supply);
// //
// //        // tatsächliches Supply der letzten Runde
// //        $query = "SELECT * FROM v_res_possessions WHERE sResource = 'Supply'";
// //        $result = $db->fetchAll($query);
// //        $real_supply = array();
// //        foreach ( $result as $key => $s )
// //        {
// //            if (!isset($real_supply[$s['nUser']]))
// //            {
// //                $real_supply[$s['nUser']] = 0;
// //            }
// //            $real_supply[$s['nUser']] = 0;
// //        }
// //        print('== real_supply: ==<br/>');
// //        Zend_Debug::dump($real_supply);
//     }
}