<?php
namespace Techtree\Table;

use Core\Table\AbstractTable;

class ColonyPersonellTable extends AbstractTable
{
    protected $table  = 'colony_personell';
    protected $primary = array('colony_id', 'personell_id');
}

