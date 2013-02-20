<?php
namespace Galaxy\Table;

use Nouron\Model\AbstractTable,
    Nouron\Model\ResultSet,
    Zend\Db\Adapter\Adapter;

class SystemObject extends AbstractTable
{
    protected $table  = 'v_glx_system_objects';

    public function __construct(Adapter $adapter)
    {
        $this->adapter = $adapter;
        $this->resultSetPrototype = new ResultSet(new \Galaxy\Entity\SystemObject());
        $this->initialize();
    }
}

