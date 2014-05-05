<?php
namespace Techtree\Service;

class PersonellService extends AbstractTechnologyService
{
    const PERSONELL_ID_ENGINEER = 35;
    const PERSONELL_ID_SCIENTIST = 36;
    const PERSONELL_ID_PILOT = 89;
    const PERSONELL_ID_DIPLOMAT = 90;
    const PERSONELL_ID_AGENT = 94;

    const DEFAULT_ACTIONPOINTS = 5;

    protected function getEntitiesTableName()
    {
        return 'personell';
    }

    protected function getColonyEntitiesTableName()
    {
        return 'colony_personell';
    }

    protected function getEntityCostsTableName()
    {
        return 'personell_costs';
    }

    protected function getEntityIdName()
    {
        return 'personell_id';
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
        switch (strtolower($type)) {
            case 'construction': $entityId = self::PERSONELL_ID_ENGINEER; break;
            case 'research':     $entityId = self::PERSONELL_ID_SCIENTIST; break;
            case 'navigation':   $entityId = self::PERSONELL_ID_PILOT; break;
            default: return 0; #TODO: error handling
        }

        $personell = $this->getColonyEntity($colonyId, $entityId);
        if (!$personell) {
            return self::DEFAULT_ACTIONPOINTS;
        } else {
            $level = $personell->getLevel();
            return ( $level * self::DEFAULT_ACTIONPOINTS + self::DEFAULT_ACTIONPOINTS );
        }
    }

    /**
     *
     * @param  numeric $colonyId
     * @return integer
     */
    public function getConstructionPoints($colonyId)
    {
        return $this->getAvailableActionPoints('construction', $colonyId);
    }

    /**
     *
     * @param  numeric $colonyId
     * @return integer
     */
    public function getResearchPoints($colonyId)
    {
        return $this->getAvailableActionPoints('research', $colonyId);
    }

    /**
     *
     * @param  numeric $fleetId
     * @return integer
     */
    public function getNavigationPoints($fleetId)
    {
        return $this->getAvailableActionPoints('navigation', $fleetId);
    }

    /**
     * get available action points for current tick
     *
     * @param  string  $type
     * @return integer
     */
    public function getAvailableActionPoints($type, $scopeId)
    {
        $this->_validateId($scopeId);
        switch (strtolower($type)) {
            case 'construction': $entityId = self::PERSONELL_ID_ENGINEER; break;
            case 'research':     $entityId = self::PERSONELL_ID_SCIENTIST; break;
            case 'navigation':   $entityId = self::PERSONELL_ID_PILOT; break;
            default: return 0;
        }

        if (strtolower($type) == 'navigation') {
            # TODO
            return 0;
        } else {

            $totalAP = $this->getTotalActionPoints($type, $scopeId);

            $data = array(
                'tick' => $this->tick,
                'colony_id' => $scopeId,
                'personell_id' => $entityId
            );

            $loggedActionpoints = $this->getTable('locked_actionpoints')
                                       ->getEntity($data);

            if ( empty($loggedActionpoints) ) {
                $loggedActionpoints = new \Techtree\Entity\ActionPoint($data);
            }

            $usedAP = $loggedActionpoints->getSpendAp();
            return ( $totalAP - $usedAP);
        }
    }

    /**
     * lock used action points for current tick
     *
     * @param string $type
     * @param integer $colonyId
     * @param integer $ap
     * @return boolean
     */
    public function lockActionPoints($type, $colonyId, $ap)
    {
        $this->_validateId($colonyId);

        $tick = $this->getTick();
        $table = $this->getTable('locked_actionpoints');
        switch (strtolower($type)) {
            case 'construction': $entityId = self::PERSONELL_ID_ENGINEER; break;
            case 'research':     $entityId = self::PERSONELL_ID_SCIENTIST; break;
            #case 'ship':         $entityId = self::PERSONELL_ID_PILOT; break;
        }
        $entity = $table->getEntity(array(
            'tick' => $tick,
            'colony_id' => $colonyId,
            'personell_id' => $entityId
        ));

        if (empty($entity)) {
            $entity = array(
                'tick' => $tick,
                'colony_id' => $colonyId,
                'personell_id' => $entityId,
                'spend_ap' => 0
            );
        } else {
            $entity = $entity->getArrayCopy();
        }

        $entity['spend_ap'] += abs((int) $ap);

        $this->getLogger()->log(
            \Zend\Log\Logger::INFO,
            "$ap actionpoints locked: " . serialize($entity)
        );

        return $table->save($entity);

    }

    /**
     *
     * @param numeric $entityId
     * @param numeric $colonyId
     * @param integer $cp
     */
    public function invest($colonyId, $entityId, $action='add', $cp=1)
    {
        return false; # TODO: points investment not needed
    }

    /**
     *
     * @param numeric $colonyId
     * @param numeric $personellId
     */
    public function hire($colonyId, $personellId)
    {
        return $this->levelup($colonyId, $personellId);
    }

    /**
     *
     * @param numeric $colonyId
     * @param numeric $personellId
     */
    public function fire($colonyId, $personellId)
    {
        return $this->leveldown($colonyId, $personellId);
    }

}