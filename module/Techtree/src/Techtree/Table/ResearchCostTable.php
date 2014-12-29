<?php
namespace Techtree\Table;

use Core\Table\AbstractTable;

class ResearchCostTable extends AbstractTable
{
    protected $table  = 'research_costs';
    protected $primary = array('research_id', 'resource_id');

}

