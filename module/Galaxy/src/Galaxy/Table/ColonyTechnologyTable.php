<?php
namespace Galaxy\Table;

use Nouron\Table\AbstractTable;
use Nouron\Model\ResultSet;
use Nouron\Entity\AbstractEntity;
use Zend\Db\Adapter\Adapter;

class ColonyTechnologyTable extends AbstractTable
{
    protected $table  = 'tech_possessions';
    protected $primary = array('colony_id', 'tech_id');

}

