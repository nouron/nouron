<?php
namespace Trade\Table;

use Nouron\Table\AbstractTable;

class ResourceTable extends AbstractTable
{
    protected $table  = 'trade_resources';
    protected $primary = array('colony_id', 'resource_id', 'direction');

}

