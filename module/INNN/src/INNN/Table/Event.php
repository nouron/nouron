<?php
namespace INNN\Table;

use Nouron\Model\AbstractTable,
    Nouron\Model\ResultSet,
    Zend\Db\Adapter\Adapter;

class Event extends AbstractTable
{
    protected $table  = 'innn_events';
    protected $primary = 'id';

    public function __construct($adapter)
    {
        $this->adapter = $adapter;
        $this->resultSetPrototype = new ResultSet(new \INNN\Entity\Event());
        $this->initialize();
    }
}

