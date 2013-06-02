<?php
namespace Galaxy\Table;

use Nouron\Model\AbstractTable,
    Nouron\Model\ResultSet,
    Zend\Db\Adapter\Adapter;

class FleetResource extends AbstractTable
{
    protected $table  = 'glx_fleetresources';
    protected $primary = array('fleet_id', 'resource_id');

    public function __construct(Adapter $adapter)
    {
        parent::__construct($adapter);
        $this->resultSetPrototype = new ResultSet(new \Galaxy\Entity\FleetResource());
        $this->initialize();
    }
}

