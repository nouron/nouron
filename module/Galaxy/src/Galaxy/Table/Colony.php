<?php
namespace Galaxy\Table;

use Nouron\Model\AbstractTable,
    Nouron\Model\ResultSet,
    Zend\Db\Adapter\Adapter;

class Colony extends AbstractTable
{
    protected $table  = 'v_glx_colonies';

    public function __construct(Adapter $adapter)
    {
        parent::__construct($adapter);
        $this->resultSetPrototype = new ResultSet(new \Galaxy\Entity\Colony());
        $this->initialize();
    }
}

