<?php
namespace FleetTest\Table;

use CoreTest\Table\AbstractTableTest;
use PHPUnit\Framework\TestCase;
use FleetTest\Bootstrap;
use Fleet\Table\FleetShipTableFactory;

class FleetShipTableFactoryTest extends AbstractTableTest
{
    public function setUp(): void
    {
        $this->initDatabaseAdapter();

        $this->sm = Bootstrap::getServiceManager();
        $this->sm->setAllowOverride(true);
        $this->sm->setService(
            'Fleet\Entity\FleetShip',
            new \Fleet\Entity\FleetShip()
        );
        $this->sm->setService(
            'Laminas\Db\Adapter\Adapter',
            $this->dbAdapter
        );
    }

    public function testCreateService()
    {
        $tableFactory = new FleetShipTableFactory();
        $this->assertInstanceOf(
            "Fleet\Table\FleetShipTable",
            $tableFactory($this->sm, '', [])
        );
    }

}