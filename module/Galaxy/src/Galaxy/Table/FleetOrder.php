<?php
namespace Galaxy\Table;

use Nouron\Model\AbstractTable,
    Nouron\Model\ResultSet,
    Zend\Db\Adapter\Adapter;

class FleetOrder extends AbstractTable
{
    protected $table  = 'glx_fleetorders';
    protected $primary = array('tick', 'fleet_id');

    public function __construct(Adapter $adapter)
    {
        parent::__construct($adapter);
        $this->resultSetPrototype = new ResultSet(new \Galaxy\Entity\FleetOrder());
        $this->initialize();
    }
}
