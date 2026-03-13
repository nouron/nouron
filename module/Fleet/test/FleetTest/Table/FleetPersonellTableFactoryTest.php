<?php
namespace FleetTest\Table;

use CoreTest\Table\AbstractTableTest;
use PHPUnit\Framework\TestCase;
use FleetTest\Bootstrap;
use Fleet\Table\FleetPersonellTableFactory;

class FleetPersonellTableFactoryTest extends AbstractTableTest
{
    public function setUp(): void
    {
        $this->initDatabaseAdapter();

        $this->sm = Bootstrap::getServiceManager();
        $this->sm->setAllowOverride(true);
        $this->sm->setService(
            'Fleet\Entity\FleetPersonell',
            new \Fleet\Entity\FleetPersonell()
        );
        $this->sm->setService(
            'Laminas\Db\Adapter\Adapter',
            $this->dbAdapter
        );
    }

    public function testCreateService()
    {
        $tableFactory = new FleetPersonellTableFactory();
        $this->assertInstanceOf(
            "Fleet\Table\FleetPersonellTable",
            $tableFactory($this->sm, '', [])
        );
    }

}