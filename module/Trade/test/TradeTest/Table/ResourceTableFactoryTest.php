<?php
namespace TradeTest\Table;

use CoreTest\Table\AbstractTableTest;
use PHPUnit_Framework_TestCase;
use TradeTest\Bootstrap;
use Trade\Table\ResourceTableFactory;

class ResourceTableFactoryTest extends AbstractTableTest
{
    public function setUp()
    {
        $this->initDatabaseAdapter();

        $this->sm = Bootstrap::getServiceManager();
        $this->sm->setAllowOverride(true);
        $this->sm->setService(
            'Trade\Entity\Resource',
            new \Trade\Entity\Resource()
        );
        $this->sm->setService(
            'Zend\Db\Adapter\Adapter',
            $this->dbAdapter
        );
    }

    public function testCreateService()
    {
        $tableFactory = new ResourceTableFactory();
        $this->assertInstanceOf(
            "Trade\Table\ResourceTable",
            $tableFactory->createService($this->sm)
        );
    }

}