<?php
namespace Trade\Table;

use Nouron\Table\AbstractTable;

class ResearchTable extends AbstractTable
{
    protected $table  = 'trade_researches';
    protected $primary = array('colony_id', 'research_id', 'direction');

}

