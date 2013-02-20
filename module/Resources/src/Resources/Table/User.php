<?php
namespace Resources\Table;

use Nouron\Model\AbstractTable,
    Nouron\Model\ResultSet,
    Zend\Db\Adapter\Adapter;

class User extends AbstractTable
{
    protected $table  = 'res_user_resources';

    public function __construct(Adapter $adapter)
    {
        $this->adapter = $adapter;
        $this->resultSetPrototype = new ResultSet(new \Resources\Entity\User());
        $this->initialize();
    }
}

