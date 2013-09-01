<?php
namespace Galaxy\Table;

use Nouron\Table\AbstractTable,
    Nouron\Model\ResultSet,
    Zend\Db\Adapter\Adapter;

class FleetTechnologyTable extends AbstractTable
{
    protected $table  = 'glx_fleettechnologies';
    protected $primary = array('fleet_id', 'tech_id');

}

