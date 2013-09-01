<?php
namespace Galaxy\Table;

use Nouron\Table\AbstractTable,
    Nouron\Model\ResultSet,
    Zend\Db\Adapter\Adapter;

class FleetResourceTable extends AbstractTable
{
    protected $table  = 'glx_fleetresources';
    protected $primary = array('fleet_id', 'resource_id');

}

