<?php
namespace TechtreeTest\Service;

use NouronTest\Service\AbstractServiceTest;
use Techtree\Service\BuildingService;
use Techtree\Table\BuildingTable;
use Techtree\Table\BuildingCostTable;
use Techtree\Table\ColonyBuildingTable;
use Techtree\Entity\Building;
use Techtree\Entity\BuildingCost;
use Techtree\Entity\ColonyBuilding;

class BuildingServiceTest extends AbstractServiceTest
{
    public function setUp()
    {
        $this->initDatabaseAdapter();

        $tableMocks = array();
        $tableMocks['buildings'] = new BuildingTable($this->dbAdapter, new Building());
        $tableMocks['building_costs']   = new BuildingCostTable($this->dbAdapter, new BuildingCost());
        $tableMocks['colony_buildings'] = new ColonyBuildingTable($this->dbAdapter, new ColonyBuilding());

        $tick = new \Nouron\Service\Tick(1234);
        #$tick->setTickCount(1234);

        $serviceMocks = array();
        $serviceMocks['resources'] = $this->getMockBuilder('Resources\Service\ResourcesService')
                                          ->disableOriginalConstructor()
                                          ->getMock();
        $serviceMocks['galaxy']    = $this->getMockBuilder('Galaxy\Service\Gateway')
                                          ->disableOriginalConstructor()
                                          ->getMock();

        $this->_service = new BuildingService($tick, $tableMocks, $serviceMocks);

        // default test parameters
        $this->_entityId = 27;
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
        $this->assertEquals('Techtree\Entity\BuildingCost', get_class($objects->current()));
    }

    public function testColonyEntity()
    {
        $possess = $this->_service->getColonyEntity($this->_colonyId, $this->_entityId);
        $this->assertEquals(5, $possess->getLevel());
    }

    public function testGetColonyEntities()
    {
        $objects = $this->_service->getColonyEntities($this->_colonyId);
        $this->assertTrue(!empty($objects));
        $this->assertEquals('Nouron\Model\ResultSet', get_class($objects));
        $this->assertEquals('Techtree\Entity\ColonyBuilding', get_class($objects->current()));
    }

    public function testGetEntities()
    {
        $objects = $this->_service->getEntities();
        $this->assertEquals('Nouron\Model\ResultSet', get_class($objects));
        $this->assertEquals('Techtree\Entity\Building', get_class($objects->current()));
    }

    public function testGetEntity()
    {
        $object = $this->_service->getEntity($this->_entityId);
        $this->assertEquals('Techtree\Entity\Building', get_class($object));
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