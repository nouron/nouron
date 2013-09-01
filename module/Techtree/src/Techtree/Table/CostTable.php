<?php
namespace Techtree\Table;

use Nouron\Table\AbstractTable;

class CostTable extends AbstractTable
{
    protected $table  = 'tech_costs';
    protected $primary = array('tech_id', 'resource_id');

}

