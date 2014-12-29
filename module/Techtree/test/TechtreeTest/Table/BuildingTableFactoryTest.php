<?php
namespace TechtreeTest\Table;

use CoreTest\Table\AbstractTableTest;
use PHPUnit_Framework_TestCase;
use TechtreeTest\Bootstrap;
use Techtree\Table\BuildingTableFactory;

class BuildingTableFactoryTest extends AbstractTableTest
{
    public function setUp()
    {
        $this->initDatabaseAdapter();

        $this->sm = Bootstrap::getServiceManager();
        $this->sm->setAllowOverride(true);
        $this->sm->setService(
            'Techtree\Entity\Building',
            new \Techtree\Entity\Building()
        );
        $this->sm->setService(
            'Zend\Db\Adapter\Adapter',
            $this->dbAdapter
        );
    }

    public function testCreateService()
    {
        $tableFactory = new BuildingTableFactory();
        $this->assertInstanceOf(
            "Techtree\Table\BuildingTable",
            $tableFactory->createService($this->sm)
        );
    }

}