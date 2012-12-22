<?php
namespace Techtree\Table;

use Nouron\Model\AbstractTable,
    Nouron\Model\ResultSet,
    Zend\Db\Adapter\Adapter;

class Possession extends AbstractTable
{
    protected $table  = 'tech_possessions';
    protected $primary = array('colony_id', 'tech_id');

    public function __construct($adapter)
    {
        $this->adapter = $adapter;
        $this->resultSetPrototype = new ResultSet(new \Techtree\Mapper\Possession());
        $this->initialize();
    }
}

