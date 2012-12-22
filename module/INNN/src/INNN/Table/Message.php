<?php
namespace INNN\Table;

use Nouron\Model\AbstractTable,
    Nouron\Model\ResultSet,
    Zend\Db\Adapter\Adapter;

class Message extends AbstractTable
{
    protected $table  = 'innn_messages';
    protected $primary = 'id';

    public function __construct($adapter)
    {
        $this->adapter = $adapter;
        $this->resultSetPrototype = new ResultSet(new \INNN\Mapper\Message());
        $this->initialize();
    }
}

