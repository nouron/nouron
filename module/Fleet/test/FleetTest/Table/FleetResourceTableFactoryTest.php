<?php
namespace FleetTest\Table;

use CoreTest\Table\AbstractTableTest;
use PHPUnit\Framework\TestCase;
use FleetTest\Bootstrap;
use Fleet\Table\FleetResourceTableFactory;

class FleetResourceTableFactoryTest extends AbstractTableTest
{
    public function setUp(): void
    {
        $this->initDatabaseAdapter();

        $this->sm = Bootstrap::getServiceManager();
        $this->sm->setAllowOverride(true);
        $this->sm->setService(
            'Fleet\Entity\FleetResource',
            new \Fleet\Entity\FleetResource()
        );
        $this->sm->setService(
            'Laminas\Db\Adapter\Adapter',
            $this->dbAdapter
        );
    }

    public function testCreateService()
    {
        $tableFactory = new FleetResourceTableFactory();
        $this->assertInstanceOf(
            "Fleet\Table\FleetResourceTable",
            $tableFactory($this->sm, '', [])
        );
    }

}