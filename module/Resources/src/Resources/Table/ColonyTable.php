<?php
namespace Resources\Table;

use Core\Table\AbstractTable;

class ColonyTable extends AbstractTable
{
    protected $table  = 'colony_resources';
    protected $primary = array('colony_id', 'resource_id');

}

