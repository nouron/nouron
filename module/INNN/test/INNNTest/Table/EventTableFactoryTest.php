<?php
namespace INNN\Test\Table;

use CoreTest\Table\AbstractTableTest;
use PHPUnit\Framework\TestCase;
use INNNTest\Bootstrap;
use INNN\Table\EventTableFactory;

class EventTableFactoryTest extends AbstractTableTest
{
    public function setUp(): void
    {
        $this->initDatabaseAdapter();

        $this->sm = Bootstrap::getServiceManager();
        $this->sm->setAllowOverride(true);
        $this->sm->setService(
            'INNN\Entity\Event',
            new \INNN\Entity\Event()
        );
        $this->sm->setService(
            'Laminas\Db\Adapter\Adapter',
            $this->dbAdapter
        );
    }

    public function testCreateService()
    {
        $tableFactory = new EventTableFactory();
        $this->assertInstanceOf(
            "INNN\Table\EventTable",
            $tableFactory($this->sm, '', [])
        );
    }

}