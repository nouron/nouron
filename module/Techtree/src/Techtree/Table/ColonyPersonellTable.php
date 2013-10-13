<?php
namespace Techtree\Table;

use Nouron\Table\AbstractTable;

class ColonyPersonellTable extends AbstractTable
{
    protected $table  = 'colony_personell';
    protected $primary = array('colony_id', 'personell_id');
}

