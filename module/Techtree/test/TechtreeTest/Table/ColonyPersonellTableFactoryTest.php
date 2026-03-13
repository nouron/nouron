<?php
namespace TechtreeTest\Table;

use CoreTest\Table\AbstractTableTest;
use PHPUnit\Framework\TestCase;
use TechtreeTest\Bootstrap;
use Techtree\Table\ColonyPersonellTableFactory;

class ColonyPersonellTableFactoryTest extends AbstractTableTest
{
    public function setUp(): void
    {
        $this->initDatabaseAdapter();

        $this->sm = Bootstrap::getServiceManager();
        $this->sm->setAllowOverride(true);
        $this->sm->setService(
            'Techtree\Entity\ColonyPersonell',
            new \Techtree\Entity\ColonyPersonell()
        );
        $this->sm->setService(
            'Laminas\Db\Adapter\Adapter',
            $this->dbAdapter
        );
    }

    public function testCreateService()
    {
        $tableFactory = new ColonyPersonellTableFactory();
        $entity = $tableFactory($this->sm, '', []);
        $this->assertInstanceOf(
            "Techtree\Table\ColonyPersonellTable",
            $entity
        );
    }

}