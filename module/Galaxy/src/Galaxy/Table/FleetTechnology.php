<?php
namespace Galaxy\Table;

use Nouron\Model\AbstractTable,
    Nouron\Model\ResultSet,
    Zend\Db\Adapter\Adapter;

class FleetTechnology extends AbstractTable
{
    protected $table  = 'glx_fleettechnologies';
    protected $primary = array('fleet_id', 'tech_id');

    public function __construct(Adapter $adapter)
    {
        parent::__construct($adapter);
        $this->resultSetPrototype = new ResultSet(new \Galaxy\Entity\FleetTechnology());
        $this->initialize();
    }
}

