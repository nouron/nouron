<?php
namespace GalaxyTest\Table;

use CoreTest\Table\AbstractTableTest;
use PHPUnit\Framework\TestCase;
use GalaxyTest\Bootstrap;
use Galaxy\Table\SystemObjectTableFactory;

class SystemObjectTableFactoryTest extends AbstractTableTest
{
    public function setUp(): void
    {
        $this->initDatabaseAdapter();

        $this->sm = Bootstrap::getServiceManager();
        $this->sm->setAllowOverride(true);
        $this->sm->setService(
            'Galaxy\Entity\SystemObject',
            new \Galaxy\Entity\SystemObject()
        );
        $this->sm->setService(
            'Laminas\Db\Adapter\Adapter',
            $this->dbAdapter
        );
    }

    public function testCreateService()
    {
        $tableFactory = new SystemObjectTableFactory();
        $this->assertInstanceOf(
            'Galaxy\Table\SystemObjectTable',
            $tableFactory($this->sm, '', [])
        );
    }

}