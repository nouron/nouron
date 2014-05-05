<?php
namespace Techtree\Service;

class ColonyService extends \Nouron\Service\AbstractService
{
    /**
     * @var integer
     */
    private $_colony_id = null;

    /**
     * @param integer
     */
    public function setColonyId($id)
    {
        $this->_validateId($id);
        $this->_colony_id = (int) $id;
    }

    /**
     * @return integer
     */
    public function getColonyId()
    {
        return $this->_colony_id;
    }

    /**
     * @return ResultSet
     */
    public function getBuildings()
    {
        return $this->getTable('colony_buildings')
                    ->fetchAll('colony_id = ' . $this->getColonyId());
    }

    /**
     * @return ResultSet
     */
    public function getResearches()
    {
        return $this->getTable('colony_researches')
                    ->fetchAll('colony_id = ' . $this->getColonyId());
    }

    /**
     * @return ResultSet
     */
    public function getShips()
    {
        return $this->getTable('colony_ships')
                    ->fetchAll('colony_id = ' . $this->getColonyId());
    }

    /**
     * @return ResultSet
     */
    public function getPersonell()
    {
        return $this->getTable('colony_personell')
                    ->fetchAll('colony_id = ' . $this->getColonyId());
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

        $hydrator = new \Zend\Stdlib\Hydrator\ClassMethods();

        $colonyEntities = $this->$func()->getArrayCopy($id);
        $colonyObjects = array();
        foreach ($colonyEntities as $id => $entity) {
            $colonyObjects[$id] = $hydrator->extract($entity);
        }

        $entities  = $this->getTable($table)->fetchAll()->getArrayCopy('id');
        $objects = array();
        foreach ($entities as $id => $entity) {
            $objects[$id] = $hydrator->extract($entity);
            $objects[$id]['level'] = 0;
            $objects[$id]['status_points'] = 0;
            $objects[$id]['ap_spend'] = 0;
            if (array_key_exists($id, $colonyObjects)) {
                $objects[$id] = $objects[$id] + $colonyObjects[$id];
            }
        }
        return $objects;
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
            'research' => $researches,
            'ship'      => $ships,
            'personell'  => $personell
        );

        return $techtree;
    }

}