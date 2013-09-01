<?php
namespace Resources\Table;

use Nouron\Table\AbstractTable;

class ColonyTable extends AbstractTable
{
    protected $table  = 'res_colony_resources';
    protected $primary = array('colony_id', 'resource_id');

}

