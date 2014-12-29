<?php
namespace Fleet\Table;

use Core\Table\AbstractTable;

class FleetPersonellTable extends AbstractTable
{
    protected $table  = 'fleet_personell';
    protected $primary = array('fleet_id', 'personell_id');

}