<?php
namespace Fleet\Table;

use Core\Table\AbstractTable;

class FleetResourceTable extends AbstractTable
{
    protected $table  = 'fleet_resources';
    protected $primary = array('fleet_id', 'resource_id');
}
