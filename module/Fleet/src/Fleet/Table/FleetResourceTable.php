<?php
namespace Fleet\Table;

use Nouron\Table\AbstractTable;

class FleetResourceTable extends AbstractTable
{
    protected $table  = 'fleet_resources';
    protected $primary = array('fleet_id', 'resource_id');
}
