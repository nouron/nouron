<?php
namespace TechtreeTest\Service;

use Techtree\Service\ShipService;
use TechtreeTest\Bootstrap;
use PHPUnit_Framework_TestCase;
use Techtree\Table\ShipTable;
use Techtree\Table\ShipCostTable;
use Techtree\Table\ColonyShipTable;
use Techtree\Entity\Ship;
use Techtree\Entity\ShipCost;
use Techtree\Entity\ColonyShip;

class ShipServiceTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $dbAdapter = new \Zend\Db\Adapter\Adapter(
            array(
                'driver' => 'Pdo_Sqlite',
                'database' => '../../../data/db/test.db'
            )
        );

        $tableMocks = array();
        $tableMocks['ships'] = new ShipTable($dbAdapter, new Ship());
        $tableMocks['ship_costs']   = new ShipCostTable($dbAdapter, new ShipCost());
        $tableMocks['colony_ships'] = new ColonyShipTable($dbAdapter, new ColonyShip());

        $tick = new \Nouron\Service\Tick(1234);
        #$tick->setTickCount(1234);

        $serviceMocks = array();
        $serviceMocks['resources'] = $this->getMockBuilder('Resources\Service\ResourcesService')
                                          ->disableOriginalConstructor()
                                          ->getMock();
        $serviceMocks['galaxy']    = $this->getMockBuilder('Galaxy\Service\Gateway')
                                          ->disableOriginalConstructor()
                                          ->getMock();

        $this->_service = new ShipService($tick, $tableMocks, $serviceMocks);

        // default test parameters
        $this->_entityId = 37;
        $this->_colonyId = 1;
    }

    public function testCheckRequiredActionPoints()
    {
        $result = $this->_service->checkRequiredActionPoints($this->_colonyId, $this->_entityId);
        $this->assertFalse($result);
        // TODO: check a positive case
        $this->markTestIncomplete();
    }

    public function testGetEntityCosts()
    {
        $objects = $this->_service->getEntityCosts($this->_entityId);
        $this->assertTrue(!empty($objects));
        $this->assertEquals('Nouron\Model\ResultSet', get_class($objects));
        $this->assertEquals('Techtree\Entity\ShipCost', get_class($objects->current()));
    }

    public function testColonyEntity()
    {
        $possess = $this->_service->getColonyEntity($this->_colonyId, $this->_entityId);
        $this->assertEquals(9, $possess->getLevel());
    }

    public function testGetColonyEntities()
    {
        $objects = $this->_service->getColonyEntities($this->_colonyId);
        $this->assertTrue(!empty($objects));
        $this->assertEquals('Nouron\Model\ResultSet', get_class($objects));
        $this->assertEquals('Techtree\Entity\ColonyShip', get_class($objects->current()));
    }

    public function testGetEntities()
    {
        $objects = $this->_service->getEntities();
        $this->assertEquals('Nouron\Model\ResultSet', get_class($objects));
        $this->assertEquals('Techtree\Entity\Ship', get_class($objects->current()));
    }

    public function testGetEntity()
    {
        $object = $this->_service->getEntity($this->_entityId);
        $this->assertEquals('Techtree\Entity\Ship', get_class($object));
        $this->markTestIncomplete();
    }

    public function testLevelUp()
    {
        // TODO: test successfull level up
        // TODO: test failed checks for level up
        // TODO: test error case
        $this->markTestIncomplete();
    }

    public function testLevelDown()
    {
        // TODO: test successfull level down
        // TODO: test failed checks for level down
        // TODO: test error case
        $this->markTestIncomplete();
    }

}