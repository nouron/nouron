<?php
namespace Colony\Service;

use Zend\Session\Container;

class ColonyService extends \Core\Service\AbstractService
{
    protected $config = array();

    public function __construct($tick, array $tables, array $services = array(), array $config)
    {
        parent::__construct($tick, $tables, $services);
        $this->config = $config;
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
     * @return \Colony\Entity\Colony
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
     * @param numeric|\Colony\Entity\Colony $colony
     * @param numeric $userId
     * @return boolean
     */
    public function checkColonyOwner($colony, $userId)
    {
        if (is_numeric($colony)) {
            $colony = $this->getColony($colony);
        }
        if ($colony && $colony->getUserId() == $userId) {
            return true;
        } else {
            return false;
        }
    }

    /**
     *
     * @param  integer $userId
     * @return \Colony\Entity\Colony|null
     * @throws Exception if no main colony was found
     */
    public function getPrimeColony($userId)
    {
        $this->_validateId($userId);
        $colonies = $this->getColoniesByUserId((int) $userId);
        foreach ($colonies as $colony) {
            if ( count($colonies) == 1 ) {
                $colony->setIsPrimary(true); /* set as prime colony*/
                // TODO: $colony->save()
            }

            if ( $colony->getIsPrimary() ) {
                return $colony;
            }
        }

        // throw exception if no primary colony could be returned
        throw new \Core\Service\Exception('No primary colony found for user.');
    }

    /**
     *
     * @param integer $selectedColonyId
     */
    public function setActiveColony($selectedColonyId)
    {
        $session = new Container('activeIds');
        if ($this->checkColonyOwner($selectedColonyId, $session->userId)) {
            $session->colonyId = $selectedColonyId;
        }
    }

    /**
     *
     * @param integer $selectedColonyId
     */
    public function setSelectedColony($selectedColonyId)
    {
        $session = new Container('selectedIds');
        $session->colonyId = $selectedColonyId;
    }

    /**
     *
     * @param  array $coords
     * @return ResultSet
     */
    public function getColoniesByCoords(array $coords)
    {
        //$config = $this->getConfig();
        $radius = round(50 / 2);

        $x1 = $coords[0] - $radius;
        $x2 = $coords[0] + $radius;
        $y1 = $coords[1] - $radius;
        $y2 = $coords[1] + $radius;

        $table = $this->getTable('colony');
        $where = "x BETWEEN $x1 AND $x2 AND y BETWEEN $y1 AND $y2";
        return $table->fetchAll($where);
    }

    /**
     * Get a colony object by its coords
     *
     * @param  array $coords
     * @return \Colony\Entity\Colony|false
     */
    public function getColonyByCoords(array $coords)
    {
        $x = $coords[0];
        $y = $coords[1];
        if (!is_numeric($x) || !is_numeric($y)) {
            throw new \Core\Service\Exception('Invalid Coordinates.');
        }
        $table = $this->getTable('systemobject');
        $planetary = $table->fetchAll("X = $x AND Y = $y")->current();


        if (!empty($planetary)) {
            // get colos on the found planetary
            // (although it is a rowset only one row is possible!)
            $colos = $this->getColoniesBySystemObjectId($planetary->getId());
            foreach ($colos as $colo) {
                // compare colony coords with given coords
                if (serialize(array($colo->getX(), $colo->getY(), $colo->getSpot()) == serialize($coords))) {
                    return $colo;
                }
            }
        }
        // return null if no colony was found
        return false;
    }

    /**
     * Get all colonies from a planetary.
     *
     * @param  integer    $planetaryId
     * @return \Colony\Entity\Colonies
     */
    public function getColoniesBySystemObjectId($planetaryId)
    {
        $this->_validateId($planetaryId);
        $table = $this->getTable('colony');
        return $table->fetchAll("system_object_id = $planetaryId");
    }


}