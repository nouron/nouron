<?php
namespace Techtree\Service;

class Requirements extends \Nouron\Service\Gateway
{
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
            $this->_requirements[$t1_id][$t2_id] =  $req->required_tech_level;
            $rowset->next();
        }

        return $this->_requirements;
    }


    /**
     * @return ResultSet
     */
    public function getRequirements()
    {
        return $this->getTable('requirement')->fetchAll();
    }


//         // Requirements holen und prüfen. Erfüllt/Nicht erfüllt setzen
//         foreach ($requirements as $t1_id => $requiredTech) {

//             $techs[$t1_id]['status'] = 'available';
//             foreach ($requiredTech as $t2_id => $values) {

//                 if ( $techs[$t1_id]['count'] > 0) {
//                     // if techlevel > 0 it is existant but can't be levelup'd
//                     if ( $techs[$t2_id]['count'] < $values['required_tech_count']) {
//                         $techs[$t1_id]['status'] = 'inactive';
//                     }
//                 } else {
//                     if ( $techs[$t2_id]['count'] <= 0) {
//                         $techs[$t1_id]['status'] = 'not available';
//                         break; // so status can not be changed by another fullfilled requirement
//                     } else {
//                         $techs[$t1_id]['status'] = 'inactive';
//                     }
//                 }
//             }
//         }

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

        $poss  = $this->getPossessionsByColonyId($colonyId)->getArrayCopy();
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

    public function checkAvailableActionPoints($type, $colonyId)
    {
        $techtreeGw = $this->getGateway['techtree'];
        switch ($type) {
            case 'build':    $maxAP  = $techtreeGw->getMaxBuildOrders($colonyId);
                             $usedAP = $techtreeGw->getUsedBuildOrders($colonyId);
                             break;
            case 'research': $maxAP  = $techtreeGw->getMaxResearchOrders($colonyId);
                             $usedAP = $techtreeGw->getUsedResearchOrders($colonyId);
                             break;
            default: $maxAP=$usedAP=0;
                     break;
        }
        return $maxAP - $usedAP;
    }
}