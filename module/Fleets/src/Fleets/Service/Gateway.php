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
     * @return \Fleets\Mapper\Fleet
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
        $x = round($coords[0] / 100);
        $y = round($coords[1] / 100);

        $table = $this->getTable('fleet');

        $where = "coordinates LIKE 'a:3:{i:0;i:".$x."__;i:1;i:".$y."%'";

        return $table->fetchAll($where);

    }
}