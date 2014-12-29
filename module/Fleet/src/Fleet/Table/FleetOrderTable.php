<?php
namespace Fleet\Table;

use Core\Table\AbstractTable;

class FleetOrderTable extends AbstractTable
{
    protected $table  = 'fleet_orders';
    protected $primary = array('tick', 'fleet_id');

}
