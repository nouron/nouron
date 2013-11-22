<?php
namespace Fleet\Table;

use Nouron\Table\AbstractTable,
    Nouron\Model\ResultSet,
    Zend\Db\Adapter\Adapter;

class FleetShipTable extends AbstractTable
{
    protected $table  = 'fleet_ships';
    protected $primary = array('fleet_id', 'ship_id');

}