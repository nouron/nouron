<?php
namespace FleetTest\Table;

use NouronTest\Table\AbstractTableTest;
use PHPUnit_Framework_TestCase;
use FleetTest\Bootstrap;
use Fleet\Table\FleetPersonellTableFactory;

class FleetPersonellTableFactoryTest extends AbstractTableTest
{
    public function setUp()
    {
        $this->initDatabaseAdapter();

        $this->sm = Bootstrap::getServiceManager();
        $this->sm->setAllowOverride(true);
        $this->sm->setService(
            'Fleet\Entity\FleetPersonell',
            new \Fleet\Entity\FleetPersonell()
        );
        $this->sm->setService(
            'Zend\Db\Adapter\Adapter',
            $this->dbAdapter
        );
    }

    public function testCreateService()
    {
        $tableFactory = new FleetPersonellTableFactory();
        $this->assertInstanceOf(
            "Fleet\Table\FleetPersonellTable",
            $tableFactory->createService($this->sm)
        );
    }

}