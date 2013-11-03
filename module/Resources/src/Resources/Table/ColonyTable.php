<?php
namespace Resources\Table;

use Nouron\Table\AbstractTable;

class ColonyTable extends AbstractTable
{
    protected $table  = 'colony_resources';
    protected $primary = array('colony_id', 'resource_id');

}

