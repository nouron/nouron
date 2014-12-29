<?php
namespace Techtree\Table;

use Core\Table\AbstractTable;

class ColonyShipTable extends AbstractTable
{
    protected $table  = 'colony_ships';
    protected $primary = array('colony_id', 'ship_id');
}

