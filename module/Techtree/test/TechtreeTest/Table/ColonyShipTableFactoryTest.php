<?php
namespace TechtreeTest\Table;

use CoreTest\Table\AbstractTableTest;
use PHPUnit\Framework\TestCase;
use TechtreeTest\Bootstrap;
use Techtree\Table\ColonyShipTableFactory;

class ColonyShipTableFactoryTest extends AbstractTableTest
{
    public function setUp(): void
    {
        $this->initDatabaseAdapter();

        $this->sm = Bootstrap::getServiceManager();
        $this->sm->setAllowOverride(true);
        $this->sm->setService(
            'Techtree\Entity\ColonyShip',
            new \Techtree\Entity\ColonyShip()
        );
        $this->sm->setService(
            'Laminas\Db\Adapter\Adapter',
            $this->dbAdapter
        );
    }

    public function testCreateService()
    {
        $tableFactory = new ColonyShipTableFactory();
        $entity = $tableFactory($this->sm, '', []);
        $this->assertInstanceOf(
            "Techtree\Table\ColonyShipTable",
            $entity
        );
    }

}