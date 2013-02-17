<?php
namespace Galaxy\Table;

use Nouron\Model\AbstractTable,
    Nouron\Model\ResultSet,
    Zend\Db\Adapter\Adapter;

class FleetTechnology extends AbstractTable
{
    protected $table  = 'glx_fleettechnologies';

    public function __construct(Adapter $adapter)
    {
        $this->adapter = $adapter;
        $this->resultSetPrototype = new ResultSet(new \Galaxy\Mapper\FleetTechnology());
        $this->initialize();
    }
}

