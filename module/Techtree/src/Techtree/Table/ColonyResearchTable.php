<?php
namespace Techtree\Table;

use Nouron\Table\AbstractTable;

class ColonyResearchTable extends AbstractTable
{
    protected $table  = 'colony_researches';
    protected $primary = array('colony_id', 'research_id');
}

