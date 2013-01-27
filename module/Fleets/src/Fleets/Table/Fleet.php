<?php
namespace Fleets\Table;

use Nouron\Model\AbstractTable,
    Nouron\Model\ResultSet,
    Zend\Db\Adapter\Adapter;

class Colony extends AbstractTable
{
    protected $table  = 'glx_fleets';

    public function __construct(Adapter $adapter)
    {
        $this->adapter = $adapter;
        $this->resultSetPrototype = new ResultSet(new \Fleets\Mapper\Colony());
        $this->initialize();
    }
}

