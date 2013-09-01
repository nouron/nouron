<?php
namespace Trade\Table;

use Nouron\Table\AbstractTable;

class TechnologyTable extends AbstractTable
{
    protected $table  = 'trade_techs';
    protected $primary = array('colony_id', 'tech_id', 'direction');

}

