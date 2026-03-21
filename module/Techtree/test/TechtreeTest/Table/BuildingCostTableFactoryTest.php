<?php
namespace TechtreeTest\Table;

use CoreTest\Table\AbstractTableTest;
use PHPUnit\Framework\TestCase;
use TechtreeTest\Bootstrap;
use Techtree\Table\BuildingCostTableFactory;

class BuildingCostTableFactoryTest extends AbstractTableTest
{
    public function setUp(): void
    {
        $this->initDatabaseAdapter();

        $this->sm = Bootstrap::getServiceManager();
        $this->sm->setAllowOverride(true);
        $this->sm->setService(
            'Techtree\Entity\BuildingCost',
            new \Techtree\Entity\BuildingCost()
        );
        $this->sm->setService(
            'Laminas\Db\Adapter\Adapter',
            $this->dbAdapter
        );
    }

    public function testCreateService()
    {
        $tableFactory = new BuildingCostTableFactory();
        $this->assertInstanceOf(
            "Techtree\Table\BuildingCostTable",
            $tableFactory($this->sm, '', [])
        );
    }

}