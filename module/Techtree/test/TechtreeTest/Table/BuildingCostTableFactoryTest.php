<?php
namespace TechtreeTest\Table;

use NouronTest\Table\AbstractTableTest;
use PHPUnit_Framework_TestCase;
use TechtreeTest\Bootstrap;
use Techtree\Table\BuildingCostTableFactory;

class BuildingCostTableFactoryTest extends AbstractTableTest
{
    public function setUp()
    {
        $this->initDatabaseAdapter();

        $this->sm = Bootstrap::getServiceManager();
        $this->sm->setAllowOverride(true);
        $this->sm->setService(
            'Techtree\Entity\BuildingCost',
            new \Techtree\Entity\BuildingCost()
        );
        $this->sm->setService(
            'Zend\Db\Adapter\Adapter',
            $this->dbAdapter
        );
    }

    public function testCreateService()
    {
        $tableFactory = new BuildingCostTableFactory();
        $this->assertInstanceOf(
            "Techtree\Table\BuildingCostTable",
            $tableFactory->createService($this->sm)
        );
    }

}