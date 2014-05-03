<?php
namespace TechtreeTest\Table;

use NouronTest\Table\AbstractTableTest;
use PHPUnit_Framework_TestCase;
use TechtreeTest\Bootstrap;
use Techtree\Table\ColonyResearchTableFactory;

class ColonyResearchTableFactoryTest extends AbstractTableTest
{
    public function setUp()
    {
        $this->initDatabaseAdapter();

        $this->sm = Bootstrap::getServiceManager();
        $this->sm->setAllowOverride(true);
        $this->sm->setService(
            'Techtree\Entity\ColonyResearch',
            new \Techtree\Entity\ColonyResearch()
        );
        $this->sm->setService(
            'Zend\Db\Adapter\Adapter',
            $this->dbAdapter
        );
    }

    public function testCreateService()
    {
        $tableFactory = new ColonyResearchTableFactory();
        $entity = $tableFactory->createService($this->sm);
        $this->assertInstanceOf(
            "Techtree\Table\ColonyResearchTable",
            $entity
        );
    }

}