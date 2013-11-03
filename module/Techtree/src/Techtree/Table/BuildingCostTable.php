<?php
namespace Techtree\Table;

use Nouron\Table\AbstractTable;

class BuildingCostTable extends AbstractTable
{
    protected $table  = 'building_costs';
    protected $primary = array('building_id', 'resource_id');

}

