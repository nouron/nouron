<?php
namespace Techtree\Table;

use Core\Table\AbstractTable;

class ActionPointTable extends AbstractTable
{
    protected $table  = 'locked_actionpoints';
    protected $primary = array('tick', 'colony_id', 'personell_id');

}

