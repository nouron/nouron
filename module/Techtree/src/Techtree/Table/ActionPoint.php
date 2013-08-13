<?php
namespace Techtree\Table;

use Nouron\Model\AbstractTable,
    Nouron\Model\ResultSet,
    Zend\Db\Adapter\Adapter;

class ActionPoint extends AbstractTable
{
    protected $table  = 'locked_actionpoints';
    protected $primary = array('tick', 'colony_id', 'personell_tech_id');

    public function __construct(Adapter $adapter)
    {
        $this->adapter = $adapter;
        $this->resultSetPrototype = new ResultSet(new \Techtree\Entity\ActionPoint());
        $this->initialize();
    }
}

