<?php
namespace Techtree\Table;

use Nouron\Table\AbstractTable;

class PossessionTable extends AbstractTable
{
    protected $table  = 'tech_possessions';
    protected $primary = array('colony_id', 'tech_id');
}

