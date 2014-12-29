<?php
namespace Trade\Table;

use Core\Table\AbstractTable;

class ResearchTable extends AbstractTable
{
    protected $table  = 'trade_researches';
    protected $primary = array('colony_id', 'research_id', 'direction');

}

