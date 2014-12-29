<?php
namespace FleetTest\Table;

use CoreTest\Table\AbstractTableTest;
use PHPUnit_Framework_TestCase;
use FleetTest\Bootstrap;
use Fleet\Table\FleetOrderTableFactory;

class FleetOrderTableFactoryTest extends AbstractTableTest
{
    public function setUp()
    {
        $this->initDatabaseAdapter();

        $this->sm = Bootstrap::getServiceManager();
        $this->sm->setAllowOverride(true);
        $this->sm->setService(
            'Fleet\Entity\FleetOrder',
            new \Fleet\Entity\FleetOrder()
        );
        $this->sm->setService(
            'Zend\Db\Adapter\Adapter',
            $this->dbAdapter
        );
    }

    public function testCreateService()
    {
        $tableFactory = new FleetOrderTableFactory();
        $this->assertInstanceOf(
            "Fleet\Table\FleetOrderTable",
            $tableFactory->createService($this->sm)
        );
    }

}