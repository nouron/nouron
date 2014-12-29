<?php
namespace FleetTest\Table;

use CoreTest\Table\AbstractTableTest;
use PHPUnit_Framework_TestCase;
use FleetTest\Bootstrap;
use Fleet\Table\FleetTableFactory;

class FleetTableFactoryTest extends AbstractTableTest
{
    public function setUp()
    {
        $this->initDatabaseAdapter();

        $this->sm = Bootstrap::getServiceManager();
        $this->sm->setAllowOverride(true);
        $this->sm->setService(
            'Fleet\Entity\Fleet',
            new \Fleet\Entity\Fleet()
        );
        $this->sm->setService(
            'Zend\Db\Adapter\Adapter',
            $this->dbAdapter
        );
    }

    public function testCreateService()
    {
        $tableFactory = new FleetTableFactory();
        $this->assertInstanceOf(
            "Fleet\Table\FleetTable",
            $tableFactory->createService($this->sm)
        );
    }

}