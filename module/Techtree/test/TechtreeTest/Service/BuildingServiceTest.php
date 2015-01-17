<?php
namespace TechtreeTest\Service;

use CoreTest\Service\AbstractServiceTest;
use Techtree\Service\BuildingService;
use Techtree\Table\BuildingTable;
use Techtree\Table\BuildingCostTable;
use Techtree\Table\ColonyBuildingTable;
use Techtree\Table\PersonellTable;
use Techtree\Entity\Building;
use Techtree\Entity\BuildingCost;
use Techtree\Entity\ColonyBuilding;
use Techtree\Entity\Personell;

class BuildingServiceTest extends AbstractServiceTest
{
    public function setUp()
    {
        $this->initDatabaseAdapter();

        // default test parameters
        $this->_entityId = 27;
        $this->_entityIdForPositiveAllChecks = 27;
        $this->_entityIdForNegativeBuildingsCheck = 28;
        $this->_entityIdForNegativeResearchesCheck = 28;
        $this->_entityIdForNegativeResourcesCheck = 28;
        $this->_entityIdForNegativeActionPointsCheck = 28;
        $this->_entityIdForNegativeMinimumCheck = 28;
        $this->_entityIdForNegativeMaximumCheck = 29;

        $this->_colonyId = 1;
        $this->_negativeColonyId = 2;

        $tables = array();
        $tables['buildings'] = new BuildingTable($this->dbAdapter, new Building());
        $tables['building_costs']   = new BuildingCostTable($this->dbAdapter, new BuildingCost());
        $tables['colony_buildings'] = new ColonyBuildingTable($this->dbAdapter, new ColonyBuilding());

        $services = array();
        $services['resources'] = $this->getMockBuilder('Resources\Service\ResourcesService')
                                      ->disableOriginalConstructor()
                                      ->getMock();

#        $services['galaxy']    = $this->getMockBuilder('Galaxy\Service\Gateway')
#                                      ->disableOriginalConstructor()
#                                      ->getMock();

        $personellService = $this->getMockBuilder('Techtree\Service\PersonellService')
                                 ->disableOriginalConstructor()
                                 ->getMock();
        $personellService->expects($this->any())
              ->method('getAvailableActionPoints')
              ->will($this->returnValueMap(array(
                    array('construction', $this->_colonyId, 50),
                    array('construction', $this->_negativeColonyId, 0),
                    array('research', $this->_colonyId, 50),
                    array('research', $this->_negativeColonyId, 0),
                    array('navigation', $this->_colonyId, 50),
                    array('navigation', $this->_negativeColonyId, 0)
                )));
        $services['personell'] = $personellService;

        $tick = new \Core\Service\Tick(1234);
        $this->_service = new BuildingService($tick, $tables, $services);
        $logger = $this->getMockBuilder('Zend\Log\Logger')
                       ->disableOriginalConstructor()
                       ->getMock();
        $this->_service->setLogger($logger);
    }

    public function testCheckRequiredActionPoints()
    {
        $this->initDatabase();

        # positive
        $result = $this->_service->checkRequiredActionPoints($this->_colonyId, $this->_entityId);
        $this->assertTrue($result);

        # negatives
        $result = $this->_service->checkRequiredActionPoints($this->_colonyId, $this->_entityIdForNegativeActionPointsCheck);
        $this->assertFalse($result);
        $result = $this->_service->checkRequiredActionPoints($this->_negativeColonyId, $this->_entityId);
        $this->assertFalse($result);

        $this->setExpectedException('Core\Service\Exception');
        $this->_service->checkRequiredActionPoints('x', 'x');
    }

    public function testGetEntityCosts()
    {
        $objects = $this->_service->getEntityCosts($this->_entityId);
        $this->assertTrue(!empty($objects));
        $this->assertEquals('Core\Model\ResultSet', get_class($objects));
        $this->assertEquals('Techtree\Entity\BuildingCost', get_class($objects->current()));
    }

    public function testColonyEntity()
    {
        $this->initDatabase();
        $possess = $this->_service->getColonyEntity($this->_colonyId, $this->_entityId);
        $this->assertEquals(5, $possess->getLevel());
    }

    public function testGetColonyEntities()
    {
        $objects = $this->_service->getColonyEntities($this->_colonyId);
        $this->assertTrue(!empty($objects));
        $this->assertEquals('Core\Model\ResultSet', get_class($objects));
        $this->assertEquals('Techtree\Entity\ColonyBuilding', get_class($objects->current()));
    }

    public function testGetEntities()
    {
        $objects = $this->_service->getEntities();
        $this->assertEquals('Core\Model\ResultSet', get_class($objects));
        $this->assertEquals('Techtree\Entity\Building', get_class($objects->current()));
    }

    public function testGetEntity()
    {
        $object = $this->_service->getEntity($this->_entityId);
        $this->assertEquals('Techtree\Entity\Building', get_class($object));
        $this->markTestIncomplete();
    }

    public function testLevelup()
    {
        $this->initDatabase();

        $possessBefore = $this->_service->getColonyEntity($this->_colonyId, $this->_entityId);

        # test successfull level up
        $result = $this->_service->levelup($this->_colonyId, $this->_entityId);
        $this->assertTrue($result);

        $possessAfter = $this->_service->getColonyEntity($this->_colonyId, $this->_entityId);
        $this->assertTrue($possessAfter->getLevel() == $possessBefore->getLevel() + 1);

        $result = $this->_service->levelup($this->_colonyId, $this->_entityIdForNegativeBuildingsCheck);
        $this->assertFalse($result);

        $result = $this->_service->levelup($this->_negativeColonyId, $this->_entityId);
        $this->assertFalse($result); # not enough construction points

        #$result = $this->_service->hire($this->_colonyId, PersonellService::PERSONELL_ID_SCIENTIST);
        #$this->assertTrue($result);
#
        #$result = $this->_service->hire($this->_colonyId, PersonellService::PERSONELL_ID_PILOT);
        #$this->assertTrue($result);

        // TODO: test failed checks for level up
        // TODO: test error case

        $this->markTestIncomplete();
    }

#    public function testLeveldown()
#    {
#        // TODO: test successfull level down
#        // TODO: test failed checks for level down
#        // TODO: test error case
#
#        $this->initDatabase();
#
#        $costs = $this->_service->getEntityCosts($this->_entityId);
#        $possessions = $this->_service->getService('resources')->getPossessionsByColonyId($this->_colonyId);
#
#        var_dump($possessions);
#
#        $before = $this->_service->getColonyEntity($this->_colonyId, $this->_entityId)->getLevel();
#        $result = $this->_service->leveldown($this->_colonyId, $this->_entityId);
#        $after = $this->_service->getColonyEntity($this->_colonyId, $this->_entityId)->getLevel();
#
#        #var_dump($result);
#        #print("\n");
#        #print($before);
#        #print("\n");
#        #print($after);
#        $this->assertTrue($before-$after==1);
#        $this->markTestIncomplete();
#    }
#
    public function testInvest()
    {
        $this->initDatabase();
        $result = $this->_service->invest($this->_colonyId, $this->_entityId, 'add', 1);
        $this->markTestIncomplete();
    }

}