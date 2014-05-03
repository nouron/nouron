<?php
namespace TechtreeTest\Table;

use NouronTest\Table\AbstractTableTest;
use PHPUnit_Framework_TestCase;
use TechtreeTest\Bootstrap;
use Techtree\Table\PersonellCostTableFactory;

class PersonellCostTableFactoryTest extends AbstractTableTest
{
    public function setUp()
    {
        $this->initDatabaseAdapter();

        $this->sm = Bootstrap::getServiceManager();
        $this->sm->setAllowOverride(true);
        $this->sm->setService(
            'Techtree\Entity\PersonellCost',
            new \Techtree\Entity\PersonellCost()
        );
        $this->sm->setService(
            'Zend\Db\Adapter\Adapter',
            $this->dbAdapter
        );
    }

    public function testCreateService()
    {
        $tableFactory = new PersonellCostTableFactory();
        $this->assertInstanceOf(
            "Techtree\Table\PersonellCostTable",
            $tableFactory->createService($this->sm)
        );
    }

}