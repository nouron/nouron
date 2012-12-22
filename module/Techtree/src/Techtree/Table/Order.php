<?php
namespace Techtree\Table;

use Nouron\Model\AbstractTable,
    Nouron\Model\ResultSet,
    Zend\Db\Adapter\Adapter;

class Order extends AbstractTable
{
    protected $table  = 'tech_orders';
    protected $primary = array('tick', 'colony_id', 'tech_id');

    public function __construct($adapter)
    {
        $this->adapter = $adapter;
        $this->resultSetPrototype = new ResultSet(new \Techtree\Mapper\Order());
        $this->initialize();
    }
}

