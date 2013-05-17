<?php
namespace Trade\Table;

use Nouron\Model\AbstractTable,
    Nouron\Model\ResultSet,
    Zend\Db\Adapter\Adapter;

class Resource extends AbstractTable
{
    protected $table  = 'trade_res';

    public function __construct(Adapter $adapter)
    {
        $this->adapter = $adapter;
        $this->resultSetPrototype = new ResultSet(new \Trade\Entity\Resource());
        $this->initialize();
    }
}

