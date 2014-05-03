<?php
namespace TechtreeTest\Table;

use NouronTest\Table\AbstractTableTest;
use PHPUnit_Framework_TestCase;
use TechtreeTest\Bootstrap;
use Techtree\Table\ResearchCostTableFactory;

class ResearchCostTableFactoryTest extends AbstractTableTest
{
    public function setUp()
    {
        $this->initDatabaseAdapter();

        $this->sm = Bootstrap::getServiceManager();
        $this->sm->setAllowOverride(true);
        $this->sm->setService(
            'Techtree\Entity\ResearchCost',
            new \Techtree\Entity\ResearchCost()
        );
        $this->sm->setService(
            'Zend\Db\Adapter\Adapter',
            $this->dbAdapter
        );
    }

    public function testCreateService()
    {
        $tableFactory = new ResearchCostTableFactory();
        $entity = $tableFactory->createService($this->sm);
        $this->assertInstanceOf(
            "Techtree\Table\ResearchCostTable",
            $entity
        );
    }

}