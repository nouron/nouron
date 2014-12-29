<?php
namespace TechtreeTest\Table;

use CoreTest\Table\AbstractTableTest;
use PHPUnit_Framework_TestCase;
use TechtreeTest\Bootstrap;
use Techtree\Table\ShipCostTableFactory;

class ShipCostTableFactoryTest extends AbstractTableTest
{
    public function setUp()
    {
        $this->initDatabaseAdapter();

        $this->sm = Bootstrap::getServiceManager();
        $this->sm->setAllowOverride(true);
        $this->sm->setService(
            'Techtree\Entity\ShipCost',
            new \Techtree\Entity\ShipCost()
        );
        $this->sm->setService(
            'Zend\Db\Adapter\Adapter',
            $this->dbAdapter
        );
    }

    public function testCreateService()
    {
        $tableFactory = new ShipCostTableFactory();
        $this->assertInstanceOf(
            "Techtree\Table\ShipCostTable",
            $tableFactory->createService($this->sm)
        );
    }

}