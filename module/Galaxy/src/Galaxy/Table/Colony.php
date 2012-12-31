<?php
namespace Galaxy\Table;

use Nouron\Model\AbstractTable,
    Nouron\Model\ResultSet,
    Zend\Db\Adapter\Adapter;

class Colony extends AbstractTable
{
    protected $table  = 'glx_colonies';

    public function __construct(Adapter $adapter)
    {
        $this->adapter = $adapter;
        $this->resultSetPrototype = new ResultSet(new \Galaxy\Mapper\Colony());
        $this->initialize();
    }
}

