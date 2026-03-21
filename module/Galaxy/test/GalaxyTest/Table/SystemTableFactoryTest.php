<?php
namespace GalaxyTest\Table;

use CoreTest\Table\AbstractTableTest;
use PHPUnit\Framework\TestCase;
use GalaxyTest\Bootstrap;
use Galaxy\Table\SystemTableFactory;

class SystemTableFactoryTest extends AbstractTableTest
{
    public function setUp(): void
    {
        $this->initDatabaseAdapter();

        $this->sm = Bootstrap::getServiceManager();
        $this->sm->setAllowOverride(true);
        $this->sm->setService(
            'Galaxy\Entity\System',
            new \Galaxy\Entity\System()
        );
        $this->sm->setService(
            'Laminas\Db\Adapter\Adapter',
            $this->dbAdapter
        );
    }

    public function testCreateService()
    {
        $tableFactory = new SystemTableFactory();
        $this->assertInstanceOf(
            'Galaxy\Table\SystemTable',
            $tableFactory($this->sm, '', [])
        );
    }

}