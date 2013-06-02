<?php
namespace Resources\Table;

use Nouron\Model\AbstractTable,
    Nouron\Model\ResultSet,
    Zend\Db\Adapter\Adapter;

class Colony extends AbstractTable
{
    protected $table  = 'res_colony_resources';
    protected $primary = array('colony_id', 'resource_id');

    public function __construct(Adapter $adapter)
    {
        $this->adapter = $adapter;
        $this->resultSetPrototype = new ResultSet(new \Resources\Entity\Colony());
        $this->initialize();
    }
}

