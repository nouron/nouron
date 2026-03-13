<?php
namespace TechtreeTest\Table;

use CoreTest\Table\AbstractTableTest;
use PHPUnit\Framework\TestCase;
use TechtreeTest\Bootstrap;
use Techtree\Table\ActionPointTableFactory;

class ActionPointTableFactoryTest extends AbstractTableTest
{
    public function setUp(): void
    {
        $this->initDatabaseAdapter();

        $this->sm = Bootstrap::getServiceManager();
        $this->sm->setAllowOverride(true);
        $this->sm->setService(
            'Techtree\Entity\ActionPoint',
            new \Techtree\Entity\ActionPoint()
        );
        $this->sm->setService(
            'Laminas\Db\Adapter\Adapter',
            $this->dbAdapter
        );
    }

    public function testCreateService()
    {
        $tableFactory = new ActionPointTableFactory();
        $this->assertInstanceOf(
            "Techtree\Table\ActionPointTable",
            $tableFactory($this->sm, '', [])
        );
    }

}