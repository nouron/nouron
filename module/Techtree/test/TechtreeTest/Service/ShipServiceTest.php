<?php
namespace TechtreeTest\Service;

use CoreTest\Service\AbstractServiceTest;
use Techtree\Service\ShipService;
use Techtree\Table\BuildingTable;
use Techtree\Table\ColonyBuildingTable;
use Techtree\Table\ColonyResearchTable;
use Techtree\Table\ShipTable;
use Techtree\Table\ShipCostTable;
use Techtree\Table\ColonyShipTable;
use Techtree\Entity\Building;
use Techtree\Entity\ColonyBuilding;
use Techtree\Entity\ColonyResearch;
use Techtree\Entity\Ship;
use Techtree\Entity\ShipCost;
use Techtree\Entity\ColonyShip;

class ShipServiceTest extends AbstractServiceTest
{
    public function setUp(): void
    {
        $this->initDatabaseAdapter();

        $tableMocks = array();
        $tableMocks['buildings']        = new BuildingTable($this->dbAdapter, new Building());
        $tableMocks['colony_buildings'] = new ColonyBuildingTable($this->dbAdapter, new ColonyBuilding());
        $tableMocks['colony_researches'] = new ColonyResearchTable($this->dbAdapter, new ColonyResearch());
        $tableMocks['ships']            = new ShipTable($this->dbAdapter, new Ship());
        $tableMocks['ship_costs']       = new ShipCostTable($this->dbAdapter, new ShipCost());
        $tableMocks['colony_ships']     = new ColonyShipTable($this->dbAdapter, new ColonyShip());

        $tick = new \Core\Service\Tick(['calculation' => ['start' => 3, 'end' => 4]], 1234);
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
        // ship 37 has ap_spend < ap_for_levelup in test DB → false
        $result = $this->_service->checkRequiredActionPoints($this->_colonyId, $this->_entityId);
        $this->assertFalse($result);
        // positive case requires investing AP first (PersonellService not mocked in this suite)

        $this->expectException('Core\Service\Exception');
        $this->_service->checkRequiredActionPoints('x', 'x');
    }

    public function testGetEntityCosts()
    {
        $objects = $this->_service->getEntityCosts($this->_entityId);
        $this->assertTrue(!empty($objects));
        $this->assertEquals('Core\Model\ResultSet', get_class($objects));
        $this->assertEquals('Techtree\Entity\ShipCost', get_class($objects->current()));
    }

    public function testGetColonyEntity()
    {
        $possess = $this->_service->getColonyEntity($this->_colonyId, $this->_entityId);
        $this->assertEquals(9, $possess->getLevel());
    }

    public function testGetColonyEntities()
    {
        $objects = $this->_service->getColonyEntities($this->_colonyId);
        $this->assertTrue(!empty($objects));
        $this->assertEquals('Core\Model\ResultSet', get_class($objects));
        $this->assertEquals('Techtree\Entity\ColonyShip', get_class($objects->current()));
    }

    public function testGetEntities()
    {
        $objects = $this->_service->getEntities();
        $this->assertEquals('Core\Model\ResultSet', get_class($objects));
        $this->assertEquals('Techtree\Entity\Ship', get_class($objects->current()));
    }

    public function testGetEntity()
    {
        $object = $this->_service->getEntity($this->_entityId);
        $this->assertEquals('Techtree\Entity\Ship', get_class($object));
        $this->assertEquals(37, $object->getId());
        $this->assertFalse($this->_service->getEntity(99999));

        $this->expectException('Core\Service\Exception');
        $this->_service->getEntity(-1);
    }

    public function testLevelUp()
    {
        $this->initDatabase();
        // ship 37 has ap_spend < ap_for_levelup → AP check fails → returns false
        $result = $this->_service->levelup($this->_colonyId, $this->_entityId);
        $this->assertFalse($result);

        $this->expectException('Core\Service\Exception');
        $this->_service->levelup(-1, $this->_entityId);
    }

    public function testLevelDown()
    {
        $this->initDatabase();
        // ship 37 has ap_spend < ap_for_levelup → AP check fails → returns false
        $result = $this->_service->leveldown($this->_colonyId, $this->_entityId);
        $this->assertFalse($result);

        $this->expectException('Core\Service\Exception');
        $this->_service->leveldown(-1, $this->_entityId);
    }

}