<?php
namespace Galaxy\Table;

use Nouron\Table\AbstractTable,
    Nouron\Model\ResultSet,
    Zend\Db\Adapter\Adapter;

class FleetOrderTable extends AbstractTable
{
    protected $table  = 'glx_fleetorders';
    protected $primary = array('tick', 'fleet_id');

}
