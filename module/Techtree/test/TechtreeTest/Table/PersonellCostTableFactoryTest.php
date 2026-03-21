<?php
namespace TechtreeTest\Table;

use CoreTest\Table\AbstractTableTest;
use PHPUnit\Framework\TestCase;
use TechtreeTest\Bootstrap;
use Techtree\Table\PersonellCostTableFactory;

class PersonellCostTableFactoryTest extends AbstractTableTest
{
    public function setUp(): void
    {
        $this->initDatabaseAdapter();

        $this->sm = Bootstrap::getServiceManager();
        $this->sm->setAllowOverride(true);
        $this->sm->setService(
            'Techtree\Entity\PersonellCost',
            new \Techtree\Entity\PersonellCost()
        );
        $this->sm->setService(
            'Laminas\Db\Adapter\Adapter',
            $this->dbAdapter
        );
    }

    public function testCreateService()
    {
        $tableFactory = new PersonellCostTableFactory();
        $this->assertInstanceOf(
            "Techtree\Table\PersonellCostTable",
            $tableFactory($this->sm, '', [])
        );
    }

}