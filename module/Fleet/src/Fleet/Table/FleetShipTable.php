<?php
namespace Fleet\Table;

use Core\Table\AbstractTable;

class FleetShipTable extends AbstractTable
{
    protected $table  = 'fleet_ships';
    protected $primary = array('fleet_id', 'ship_id');

}