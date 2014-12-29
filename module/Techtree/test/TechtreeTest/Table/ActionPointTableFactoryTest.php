<?php
namespace TechtreeTest\Table;

use CoreTest\Table\AbstractTableTest;
use PHPUnit_Framework_TestCase;
use TechtreeTest\Bootstrap;
use Techtree\Table\ActionPointTableFactory;

class ActionPointTableFactoryTest extends AbstractTableTest
{
    public function setUp()
    {
        $this->initDatabaseAdapter();

        $this->sm = Bootstrap::getServiceManager();
        $this->sm->setAllowOverride(true);
        $this->sm->setService(
            'Techtree\Entity\ActionPoint',
            new \Techtree\Entity\ActionPoint()
        );
        $this->sm->setService(
            'Zend\Db\Adapter\Adapter',
            $this->dbAdapter
        );
    }

    public function testCreateService()
    {
        $tableFactory = new ActionPointTableFactory();
        $this->assertInstanceOf(
            "Techtree\Table\ActionPointTable",
            $tableFactory->createService($this->sm)
        );
    }

}