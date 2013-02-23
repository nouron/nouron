<?php
namespace Galaxy\Table;

use Nouron\Model\AbstractTable,
    Nouron\Model\ResultSet,
    Zend\Db\Adapter\Adapter,
    Zend\Db\TableGateway\Feature\RowGatewayFeature;

class ColonyTechnology extends AbstractTable
{
    protected $table  = 'tech_possessions';
    protected $primary = array('colony_id', 'tech_id');

    public function __construct(Adapter $adapter)
    {
        parent::__construct($adapter);
        $this->resultSetPrototype = new ResultSet(new \Galaxy\Entity\ColonyTechnology());
        $this->initialize();
    }
}

