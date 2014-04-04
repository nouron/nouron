<?php
namespace Fleet\Table;

use Nouron\Table\AbstractTable;

class FleetResearchTable extends AbstractTable
{
    protected $table  = 'fleet_researches';
    protected $primary = array('fleet_id', 'research_id');

}

