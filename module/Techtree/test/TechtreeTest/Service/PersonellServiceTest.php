<?php
namespace TechtreeTest\Service;

use CoreTest\Service\AbstractServiceTest;
use Techtree\Service\PersonellService;
use Techtree\Table\ActionPointTable;
use Techtree\Table\BuildingTable;
use Techtree\Table\ResearchTable;
use Techtree\Table\PersonellTable;
use Techtree\Table\ShipTable;
use Techtree\Table\PersonellCostTable;
use Techtree\Table\ColonyBuildingTable;
use Techtree\Table\ColonyResearchTable;
use Techtree\Table\ColonyPersonellTable;
use Techtree\Table\ColonyShipTable;
use Techtree\Entity\ActionPoint;
use Techtree\Entity\Building;
use Techtree\Entity\Research;
use Techtree\Entity\Ship;
use Techtree\Entity\Personell;
use Techtree\Entity\PersonellCost;
use Techtree\Entity\ColonyBuilding;
use Techtree\Entity\ColonyResearch;
use Techtree\Entity\ColonyPersonell;
use Techtree\Entity\ColonyShip;

class PersonellServiceTest extends AbstractServiceTest
{
    public function setUp()
    {
        $this->initDatabaseAdapter();

        // default test parameters
        $this->_entityId = 35;
        $this->_colonyId = 1;

        $tables = array();
        $tables['buildings'] = new BuildingTable($this->dbAdapter, new Building());
        $tables['researches'] = new ResearchTable($this->dbAdapter, new Research());
        $tables['ships'] = new ShipTable($this->dbAdapter, new Ship());
        $tables['personell'] = new PersonellTable($this->dbAdapter, new Personell());
        $tables['personell_costs']   = new PersonellCostTable($this->dbAdapter, new PersonellCost());
        $tables['colony_buildings'] = new ColonyBuildingTable($this->dbAdapter, new ColonyBuilding());
        $tables['colony_researches'] = new ColonyResearchTable($this->dbAdapter, new ColonyResearch());
        $tables['colony_personell'] = new ColonyPersonellTable($this->dbAdapter, new ColonyPersonell());
        $tables['colony_ships'] = new ColonyShipTable($this->dbAdapter, new ColonyShip());
        $tables['locked_actionpoints'] = new ActionPointTable($this->dbAdapter, new ActionPoint());

        $tick = new \Core\Service\Tick(1234);
        #$tick->setTickCount(1234);

        $serviceMocks = array();
        $serviceMocks['galaxy']    = $this->getMockBuilder('Galaxy\Service\Gateway')
                                          ->disableOriginalConstructor()
                                          ->getMock();
        $resourcesService = $this->getMockBuilder('Resources\Service\ResourcesService')
                                          ->disableOriginalConstructor()
                                          ->getMock();
        $resourcesService->expects($this->any())->method('check')->will($this->returnValue(true));
        $serviceMocks['resources'] = $resourcesService;

        $this->_service = new PersonellService($tick, $tables, $serviceMocks);

    }

    public function testCheckRequiredActionPoints()
    {
        $result = $this->_service->checkRequiredActionPoints($this->_colonyId, $this->_entityId);
        $this->assertTrue($result);
    }

    public function testGetEntityCosts()
    {
        $objects = $this->_service->getEntityCosts($this->_entityId);
        $this->assertTrue(!empty($objects));
        $this->assertEquals('Core\Model\ResultSet', get_class($objects));
        $this->assertEquals('Techtree\Entity\PersonellCost', get_class($objects->current()));
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
        $this->assertEquals('Core\Model\ResultSet', get_class($objects));
        $this->assertEquals('Techtree\Entity\ColonyPersonell', get_class($objects->current()));
    }

    public function testGetEntities()
    {
        $objects = $this->_service->getEntities();
        $this->assertEquals('Core\Model\ResultSet', get_class($objects));
        $this->assertEquals('Techtree\Entity\Personell', get_class($objects->current()));
    }

    public function testGetEntity()
    {
        $object = $this->_service->getEntity($this->_entityId);
        $this->assertEquals('Techtree\Entity\Personell', get_class($object));
        $this->markTestIncomplete();
    }

    public function testGetTotalActionPoints()
    {
        $points = $this->_service->getTotalActionPoints('construction', $this->_colonyId);
        $this->assertTrue($points > PersonellService::DEFAULT_ACTIONPOINTS);
        $points = $this->_service->getTotalActionPoints('research', $this->_colonyId);
        $this->assertTrue($points > PersonellService::DEFAULT_ACTIONPOINTS);
        $points = $this->_service->getTotalActionPoints('navigation', $this->_colonyId);
        $this->assertTrue($points == PersonellService::DEFAULT_ACTIONPOINTS);

    }

    public function testGetConstructionPoints()
    {
        $points = $this->_service->getConstructionPoints($this->_colonyId);
        $this->assertTrue($points > 0);
    }

    public function testGetResearchPoints()
    {
        $points = $this->_service->getResearchPoints($this->_colonyId);
        $this->assertTrue($points > 0);
    }

    public function testGetNavigationPoints()
    {
        $points = $this->_service->getNavigationPoints($this->_colonyId);
        $this->assertTrue($points == 0);
    }

    public function testGetAvailableActionPoints()
    {
        $points = $this->_service->getAvailableActionPoints('construction', $this->_colonyId);
        $this->assertTrue($points > 0);
        $points = $this->_service->getAvailableActionPoints('research', $this->_colonyId);
        $this->assertTrue($points > 0);
        $points = $this->_service->getAvailableActionPoints('navigation', $this->_colonyId);
        $this->assertTrue($points == 0);
    }

    public function testLockActionPoints()
    {
        $this->markTestIncomplete();
    }

    public function testInvest()
    {
        $this->markTestIncomplete();
    }

    public function testHire()
    {
        $this->initDatabase();

        $result = $this->_service->hire($this->_colonyId, PersonellService::PERSONELL_ID_ENGINEER);
        $this->assertTrue($result);

        $result = $this->_service->hire($this->_colonyId, PersonellService::PERSONELL_ID_SCIENTIST);
        $this->assertTrue($result);

        $result = $this->_service->hire($this->_colonyId, PersonellService::PERSONELL_ID_PILOT);
        $this->assertTrue($result);

        #$result = $this->_service->hire($this->_colonyId, PersonellService::PERSONELL_ID_DIPLOMAT);
        #$this->assertTrue($result);

        #$result = $this->_service->hire($this->_colonyId, PersonellService::PERSONELL_ID_AGENT);
        #$this->assertTrue($result);

        $this->markTestIncomplete();
    }

    public function testFire()
    {
        $this->initDatabase();
#        $result = $this->_service->fire($this->_colonyId, PersonellService::PERSONELL_ID_ENGINEER);
#        $this->assertTrue($result);
#
#        $result = $this->_service->fire($this->_colonyId, PersonellService::PERSONELL_ID_SCIENTIST);
#        $this->assertTrue($result);
#
#        $result = $this->_service->fire($this->_colonyId, PersonellService::PERSONELL_ID_PILOT);
#        $this->assertTrue($result);
#
#        $result = $this->_service->fire($this->_colonyId, PersonellService::PERSONELL_ID_DIPLOMAT);
#        $this->assertTrue($result);
#
#        $result = $this->_service->fire($this->_colonyId, PersonellService::PERSONELL_ID_AGENT);
#        $this->assertTrue($result);
#
        $this->markTestIncomplete();
    }

}