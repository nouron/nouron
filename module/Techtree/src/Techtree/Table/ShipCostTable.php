<?php
namespace Techtree\Table;

use Core\Table\AbstractTable;

class ShipCostTable extends AbstractTable
{
    protected $table  = 'ship_costs';
    protected $primary = array('ship_id', 'resource_id');

}

