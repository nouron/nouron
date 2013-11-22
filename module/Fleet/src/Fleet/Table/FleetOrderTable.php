<?php
namespace Fleet\Table;

use Nouron\Table\AbstractTable,
    Nouron\Model\ResultSet,
    Zend\Db\Adapter\Adapter;

class FleetOrderTable extends AbstractTable
{
    protected $table  = 'fleet_orders';
    protected $primary = array('tick', 'fleet_id');

}
