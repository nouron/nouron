<?php
namespace TradeTest\Table;

use CoreTest\Table\AbstractTableTest;
use PHPUnit\Framework\TestCase;
use TradeTest\Bootstrap;
use Trade\Table\ResearchTableFactory;

class ResearchTableFactoryTest extends AbstractTableTest
{
    public function setUp(): void
    {
        $this->initDatabaseAdapter();

        $this->sm = Bootstrap::getServiceManager();
        $this->sm->setAllowOverride(true);
        $this->sm->setService(
            'Trade\Entity\Research',
            new \Trade\Entity\Research()
        );
        $this->sm->setService(
            'Laminas\Db\Adapter\Adapter',
            $this->dbAdapter
        );
    }

    public function testCreateService()
    {
        $tableFactory = new ResearchTableFactory();
        $this->assertInstanceOf(
            "Trade\Table\ResearchTable",
            $tableFactory($this->sm, '', [])
        );
    }

}