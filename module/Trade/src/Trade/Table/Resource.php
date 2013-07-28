<?php
namespace Trade\Table;

use Nouron\Model\AbstractTable,
    Nouron\Model\ResultSet,
    Zend\Db\Adapter\Adapter;

class Resource extends AbstractTable
{
    protected $table  = 'trade_resources';
    protected $primary = array('colony_id', 'resource_id', 'direction');

    public function __construct(Adapter $adapter)
    {
        $this->adapter = $adapter;
        $this->resultSetPrototype = new ResultSet(new \Trade\Entity\Resource());
        $this->initialize();
    }
}

