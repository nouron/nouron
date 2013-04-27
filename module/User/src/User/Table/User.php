<?php
namespace User\Table;

use Nouron\Model\AbstractTable,
    Nouron\Model\ResultSet,
    Zend\Db\Adapter\Adapter;

class User extends AbstractTable
{
    protected $table  = 'user';
    protected $primary = 'id';

    public function __construct($adapter)
    {
        $this->adapter = $adapter;
        $this->resultSetPrototype = new ResultSet(new \User\Entity\User());
        $this->initialize();
    }
}

