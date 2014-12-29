<?php
namespace FleetTest\Table;

use CoreTest\Table\AbstractTableTest;
use PHPUnit_Framework_TestCase;
use FleetTest\Bootstrap;
use Fleet\Table\FleetResourceTableFactory;

class FleetResourceTableFactoryTest extends AbstractTableTest
{
    public function setUp()
    {
        $this->initDatabaseAdapter();

        $this->sm = Bootstrap::getServiceManager();
        $this->sm->setAllowOverride(true);
        $this->sm->setService(
            'Fleet\Entity\FleetResource',
            new \Fleet\Entity\FleetResource()
        );
        $this->sm->setService(
            'Zend\Db\Adapter\Adapter',
            $this->dbAdapter
        );
    }

    public function testCreateService()
    {
        $tableFactory = new FleetResourceTableFactory();
        $this->assertInstanceOf(
            "Fleet\Table\FleetResourceTable",
            $tableFactory->createService($this->sm)
        );
    }

}