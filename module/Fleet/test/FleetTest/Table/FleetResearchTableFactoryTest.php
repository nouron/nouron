<?php
namespace FleetTest\Table;

use CoreTest\Table\AbstractTableTest;
use PHPUnit_Framework_TestCase;
use FleetTest\Bootstrap;
use Fleet\Table\FleetResearchTableFactory;

class FleetResearchTableFactoryTest extends AbstractTableTest
{
    public function setUp()
    {
        $this->initDatabaseAdapter();

        $this->sm = Bootstrap::getServiceManager();
        $this->sm->setAllowOverride(true);
        $this->sm->setService(
            'Fleet\Entity\FleetResearch',
            new \Fleet\Entity\FleetResearch()
        );
        $this->sm->setService(
            'Zend\Db\Adapter\Adapter',
            $this->dbAdapter
        );
    }

    public function testCreateService()
    {
        $tableFactory = new FleetResearchTableFactory();
        $this->assertInstanceOf(
            "Fleet\Table\FleetResearchTable",
            $tableFactory->createService($this->sm)
        );
    }

}