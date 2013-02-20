<?php
namespace Fleets\Service;

class Gateway extends \Nouron\Service\Gateway
{
    public function __construct($tick, array $tables, array $gateways = array())
    {
        $this->setTick($tick);
        $this->setTables($tables);
        $this->setGateways($gateways);
    }

    /**
     * @return \Fleets\Entity\Fleet
     */
    public function getFleet($fleetId)
    {
        $this->_validateId($colonyId);
        return $this->getTable('fleet')->getEntity($fleetId);
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
     * get all fleets in a system
     * @TODO this function is very similar to $this->getColoniesBySystemCoordinates,
     *       maybe its possible to merge this to function and parameterize it?
     *
     * @param  array $coords
     * @return ResultSet
     */
    public function getFleetsBySystemCoordinates(array $coords)
    {
        //$config = $this->getConfig();
        $radius = round(100 / 2);

        $x1 = $coords[0] - $radius;
        $x2 = $coords[0] + $radius;
        $y1 = $coords[1] - $radius;
        $y2 = $coords[1] + $radius;

        $table = $this->getTable('fleet');
        return $table->fetchAll("x BETWEEN $x1 AND $x2 AND y BETWEEN $y1 AND $y2");

    }
}