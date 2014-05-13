<?php
namespace INNN\Test\Table;

use NouronTest\Table\AbstractTableTest;
use PHPUnit_Framework_TestCase;
use INNNTest\Bootstrap;
use INNN\Table\EventTableFactory;

class EventTableFactoryTest extends AbstractTableTest
{
    public function setUp()
    {
        $this->initDatabaseAdapter();

        $this->sm = Bootstrap::getServiceManager();
        $this->sm->setAllowOverride(true);
        $this->sm->setService(
            'INNN\Entity\Event',
            new \INNN\Entity\Event()
        );
        $this->sm->setService(
            'Zend\Db\Adapter\Adapter',
            $this->dbAdapter
        );
    }

    public function testCreateService()
    {
        $tableFactory = new EventTableFactory();
        $this->assertInstanceOf(
            "INNN\Table\EventTable",
            $tableFactory->createService($this->sm)
        );
    }

}