<?php
namespace Fleet\Table;

use Nouron\Table\AbstractTable,
    Nouron\Model\ResultSet,
    Zend\Db\Adapter\Adapter;

class FleetResourceTable extends AbstractTable
{
    protected $table  = 'fleet_resources';
    protected $primary = array('fleet_id', 'resource_id');
}
