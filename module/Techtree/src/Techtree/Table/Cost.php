<?php
namespace Techtree\Table;

use Nouron\Model\AbstractTable,
    Nouron\Model\ResultSet,
    Zend\Db\Adapter\Adapter;

class Cost extends AbstractTable
{
    protected $table  = 'tech_costs';
    protected $primary = array('tech_id', 'resource_id');

    public function __construct($adapter)
    {
        $this->adapter = $adapter;
        $this->resultSetPrototype = new ResultSet(new \Techtree\Mapper\Cost());
        $this->initialize();
    }
}

