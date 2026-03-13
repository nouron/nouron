<?php
namespace TechtreeTest\Table;

use CoreTest\Table\AbstractTableTest;
use PHPUnit\Framework\TestCase;
use TechtreeTest\Bootstrap;
use Techtree\Table\ColonyResearchTableFactory;

class ColonyResearchTableFactoryTest extends AbstractTableTest
{
    public function setUp(): void
    {
        $this->initDatabaseAdapter();

        $this->sm = Bootstrap::getServiceManager();
        $this->sm->setAllowOverride(true);
        $this->sm->setService(
            'Techtree\Entity\ColonyResearch',
            new \Techtree\Entity\ColonyResearch()
        );
        $this->sm->setService(
            'Laminas\Db\Adapter\Adapter',
            $this->dbAdapter
        );
    }

    public function testCreateService()
    {
        $tableFactory = new ColonyResearchTableFactory();
        $entity = $tableFactory($this->sm, '', []);
        $this->assertInstanceOf(
            "Techtree\Table\ColonyResearchTable",
            $entity
        );
    }

}