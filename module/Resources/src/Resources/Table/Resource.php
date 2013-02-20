<?php
namespace Resources\Table;

use Nouron\Model\AbstractTable,
    Nouron\Model\ResultSet,
    Zend\Db\Adapter\Adapter;

class Resource extends AbstractTable
{
    protected $table  = 'res_resources';

    public function __construct(Adapter $adapter)
    {
        $this->adapter = $adapter;
        $this->resultSetPrototype = new ResultSet(new \Resources\Entity\Resource());
        $this->initialize();
    }
}

