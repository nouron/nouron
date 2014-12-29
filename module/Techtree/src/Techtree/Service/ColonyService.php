<?php
namespace Techtree\Service;

class ColonyService extends \Core\Service\AbstractService
{
    /**
     * @var integer
     */
    private $scopeColonyId = null;

    public function __construct($tick, $tables, $services, $scopeColonyId = null)
    {
        parent::__construct($tick, $tables, $services);
        if (!empty($scopeColonyId)) {
            $this->setScopeColonyId($scopeColonyId);
        } else {
            $this->getLogger()->log(\Zend\Log\Logger::WARN, 'Missing scope colony id. Some methods can fail unexpectedly!');
        }
    }

    /**
     * @param integer
     */
    public function setScopeColonyId($colonyId)
    {
        $this->_validateId($colonyId);
        $this->scopeColonyId = (int) $colonyId;
    }

    /**
     * @return integer
     */
    public function getScopeColonyId()
    {
        return $this->scopeColonyId;
    }

    /**
     * @return ResultSet
     */
    public function getBuildings()
    {
        return $this->getTable('colony_buildings')
                    ->fetchAll('colony_id = ' . $this->getScopeColonyId());
    }

    /**
     * @return ResultSet
     */
    public function getResearches()
    {
        return $this->getTable('colony_researches')
                    ->fetchAll('colony_id = ' . $this->getScopeColonyId());
    }

    /**
     * @return ResultSet
     */
    public function getShips()
    {
        return $this->getTable('colony_ships')
                    ->fetchAll('colony_id = ' . $this->getScopeColonyId());
    }

    /**
     * @return ResultSet
     */
    public function getPersonell()
    {
        return $this->getTable('colony_personell')
                    ->fetchAll('colony_id = ' . $this->getScopeColonyId());
    }

    /**
     *
     * @param  string $type
     * @return array
     */
    private function _gatherTechtreeInformations($type)
    {
        switch (strtolower($type)) {
            case 'building': $table = 'buildings';
                             $id    = 'building_id';
                             $func  = 'getBuildings';
                             break;
            case 'research': $table = 'researches';
                             $id    = 'research_id';
                             $func  = 'getResearches';
                             break;
            case 'ship':     $table = 'ships';
                             $id    = 'ship_id';
                             $func  = 'getShips';
                             break;
            case 'personell':$table = 'personell';
                             $id    = 'personell_id';
                             $func  = 'getPersonell';
                             break;
            default:        return array(); # TODO: Exception
                            break;
        }

        $colonyEntities = $this->$func()->getArrayCopy($id);
        $entities  = $this->getTable($table)->fetchAll()->getArrayCopy('id');
        foreach ($entities as $id => $entity) {
            if (array_key_exists($id, $colonyEntities)) {
                $entities[$id] = $entities[$id] + $colonyEntities[$id];
            } else {
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
    public function getTechtree()
    {
        $buildings  = $this->_gatherTechtreeInformations('building');
        $researches = $this->_gatherTechtreeInformations('research');
        $ships      = $this->_gatherTechtreeInformations('ship');
        $personell  = $this->_gatherTechtreeInformations('personell');

        $techtree = array(
            'building'  => $buildings,
            'research'  => $researches,
            'ship'      => $ships,
            'personell' => $personell
        );

        return $techtree;
    }

}