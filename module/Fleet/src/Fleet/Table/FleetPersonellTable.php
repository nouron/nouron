<?php
namespace Fleet\Table;

use Nouron\Table\AbstractTable;

class FleetPersonellTable extends AbstractTable
{
    protected $table  = 'fleet_personell';
    protected $primary = array('fleet_id', 'personell_id');

}