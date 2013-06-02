<?php
namespace Trade\Table;

use Nouron\Model\AbstractTable,
    Nouron\Model\ResultSet,
    Zend\Db\Adapter\Adapter;

class Technology extends AbstractTable
{
    protected $table  = 'trade_techs';

    public function __construct(Adapter $adapter)
    {
        $this->adapter = $adapter;
        $this->resultSetPrototype = new ResultSet(new \Trade\Entity\Technology());
        $this->initialize();
    }
}
