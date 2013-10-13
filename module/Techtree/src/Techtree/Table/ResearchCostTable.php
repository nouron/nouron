<?php
namespace Techtree\Table;

use Nouron\Table\AbstractTable;

class ResearchCostTable extends AbstractTable
{
    protected $table  = 'research_costs';
    protected $primary = array('research_id', 'resource_id');

}

