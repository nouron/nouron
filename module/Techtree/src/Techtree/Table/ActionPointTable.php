<?php
namespace Techtree\Table;

use Nouron\Table\AbstractTable;

class ActionPointTable extends AbstractTable
{
    protected $table  = 'locked_actionpoints';
    protected $primary = array('tick', 'colony_id', 'personell_tech_id');

}

