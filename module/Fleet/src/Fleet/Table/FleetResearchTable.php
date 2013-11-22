<?php
namespace Fleet\Table;

use Nouron\Table\AbstractTable,
    Nouron\Model\ResultSet,
    Zend\Db\Adapter\Adapter;

class FleetResearchTable extends AbstractTable
{
    protected $table  = 'fleet_researches';
    protected $primary = array('fleet_id', 'research_id');

}

