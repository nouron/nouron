<?php
namespace TradeTest\Table;

use NouronTest\Table\AbstractTableTest;
use PHPUnit_Framework_TestCase;
use TechtreeTest\Bootstrap;
use Trade\Table\ResearchViewFactory;

class ResearchViewFactoryTest extends AbstractTableTest
{
    public function setUp()
    {
        $this->initDatabaseAdapter();

        $this->sm = Bootstrap::getServiceManager();
        $this->sm->setAllowOverride(true);
        $this->sm->setService(
            'Trade\Entity\Research',
            new \Trade\Entity\Research()
        );
        $this->sm->setService(
            'Zend\Db\Adapter\Adapter',
            $this->dbAdapter
        );
    }

    public function testCreateService()
    {
        $tableFactory = new ResearchViewFactory();
        $this->assertInstanceOf(
            "Trade\Table\ResearchView",
            $tableFactory->createService($this->sm)
        );
    }

}