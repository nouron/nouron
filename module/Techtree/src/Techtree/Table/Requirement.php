<?php
namespace Techtree\Table;

use Nouron\Model\AbstractTable,
    Nouron\Model\ResultSet,
    Zend\Db\Adapter\Adapter;

class Requirement extends AbstractTable
{
    protected $table  = 'v_tech_requirements';
    protected $primary = array('tech_id', 'required_tech_id');

    public function __construct($adapter)
    {
        $this->adapter = $adapter;
        $this->resultSetPrototype = new ResultSet(new \Techtree\Entity\Requirement());
        $this->initialize();
    }
}

