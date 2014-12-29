<?php
namespace TechtreeTest\Table;

use CoreTest\Table\AbstractTableTest;
use PHPUnit_Framework_TestCase;
use TechtreeTest\Bootstrap;
use Techtree\Table\ColonyShipTableFactory;

class ColonyShipTableFactoryTest extends AbstractTableTest
{
    public function setUp()
    {
        $this->initDatabaseAdapter();

        $this->sm = Bootstrap::getServiceManager();
        $this->sm->setAllowOverride(true);
        $this->sm->setService(
            'Techtree\Entity\ColonyShip',
            new \Techtree\Entity\ColonyShip()
        );
        $this->sm->setService(
            'Zend\Db\Adapter\Adapter',
            $this->dbAdapter
        );
    }

    public function testCreateService()
    {
        $tableFactory = new ColonyShipTableFactory();
        $entity = $tableFactory->createService($this->sm);
        $this->assertInstanceOf(
            "Techtree\Table\ColonyShipTable",
            $entity
        );
    }

}