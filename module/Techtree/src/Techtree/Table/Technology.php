<?php
namespace Techtree\Table;

use Nouron\Model\AbstractTable,
    Nouron\Model\ResultSet,
    Zend\Db\Adapter\Adapter;

class Technology extends AbstractTable
{
    protected $table  = 'tech_technologies';

    public function __construct(Adapter $adapter)
    {
        $this->adapter = $adapter;
        $this->resultSetPrototype = new ResultSet(new \Techtree\Mapper\Technology());
        $this->initialize();
    }
}

