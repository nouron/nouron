<?php
namespace Techtree\Table;

use Nouron\Table\AbstractTable;

class OrderTable extends AbstractTable
{
    protected $table  = 'tech_orders';
    protected $primary = array('tick', 'colony_id', 'tech_id');

}

