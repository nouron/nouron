<?php
namespace Fleet\Table;

use Nouron\Table\AbstractTable;

class FleetOrderTable extends AbstractTable
{
    protected $table  = 'fleet_orders';
    protected $primary = array('tick', 'fleet_id');

}
