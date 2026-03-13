<?php
namespace TechtreeTest\Table;

use CoreTest\Table\AbstractTableTest;
use PHPUnit\Framework\TestCase;
use TechtreeTest\Bootstrap;
use Techtree\Table\ColonyBuildingTableFactory;

class ColonyBuildingTableFactoryTest extends AbstractTableTest
{
    public function setUp(): void
    {
        $this->initDatabaseAdapter();

        $this->sm = Bootstrap::getServiceManager();
        $this->sm->setAllowOverride(true);
        $this->sm->setService(
            'Techtree\Entity\ColonyBuilding',
            new \Techtree\Entity\ColonyBuilding()
        );
        $this->sm->setService(
            'Laminas\Db\Adapter\Adapter',
            $this->dbAdapter
        );
    }

    public function testCreateService()
    {
        $tableFactory = new ColonyBuildingTableFactory();
        $entity = $tableFactory($this->sm, '', []);
        $this->assertInstanceOf(
            "Techtree\Table\ColonyBuildingTable",
            $entity
        );
    }

}