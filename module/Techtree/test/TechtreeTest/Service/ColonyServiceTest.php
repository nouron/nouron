<?php
namespace TechtreeTest\Service;

use NouronTest\Service\AbstractServiceTest;
use Techtree\Service\ColonyService;
use Techtree\Table\BuildingTable;
use Techtree\Table\ResearchTable;
use Techtree\Table\PersonellTable;
use Techtree\Table\ShipTable;
use Techtree\Table\BuildingCostTable;
use Techtree\Table\ColonyBuildingTable;
use Techtree\Table\ColonyResearchTable;
use Techtree\Table\ColonyPersonellTable;
use Techtree\Table\ColonyShipTable;
use Techtree\Entity\Building;
use Techtree\Entity\Research;
use Techtree\Entity\Ship;
use Techtree\Entity\Personell;
use Techtree\Entity\BuildingCost;
use Techtree\Entity\ColonyBuilding;
use Techtree\Entity\ColonyResearch;
use Techtree\Entity\ColonyPersonell;
use Techtree\Entity\ColonyShip;

class ColonyServiceTest extends AbstractServiceTest
{
    /**
     * @var integer
     */
    private $_colonyId = null;

    public function setUp()
    {
        $this->initDatabaseAdapter();

        // default test parameters
        $this->_entityId = 27;
        $this->_colonyId = 1;

        $tables = array();
        $tables['buildings'] = new BuildingTable($this->dbAdapter, new Building());
        $tables['researches'] = new ResearchTable($this->dbAdapter, new Research());
        $tables['ships'] = new ShipTable($this->dbAdapter, new Ship());
        $tables['personell'] = new PersonellTable($this->dbAdapter, new Personell());
        $tables['building_costs']   = new BuildingCostTable($this->dbAdapter, new BuildingCost());
        $tables['colony_buildings'] = new ColonyBuildingTable($this->dbAdapter, new ColonyBuilding());
        $tables['colony_researches'] = new ColonyResearchTable($this->dbAdapter, new ColonyResearch());
        $tables['colony_personell'] = new ColonyPersonellTable($this->dbAdapter, new ColonyPersonell());
        $tables['colony_ships'] = new ColonyShipTable($this->dbAdapter, new ColonyShip());

        $tick = new \Nouron\Service\Tick(1234);
        #$tick->setTickCount(1234);

        $services = array();
#        $services['resources'] = $this->getMockBuilder('Resources\Service\ResourcesService')
#                                      ->disableOriginalConstructor()
#                                      ->getMock();
#
#        $services['galaxy']    = $this->getMockBuilder('Galaxy\Service\Gateway')
#                                      ->disableOriginalConstructor()
#                                      ->getMock();

        $this->_service = new ColonyService($tick, $tables, $services, $this->_colonyId);
        $logger = $this->getMockBuilder('Zend\Log\Logger')
                       ->disableOriginalConstructor()
                       ->getMock();
        $this->_service->setLogger($logger);
    }

    public function testSetScopeColonyId()
    {
        $tick = new \Nouron\Service\Tick(1234);
        $service = new ColonyService($tick, array(), array(), $this->_colonyId);
        $service->setScopeColonyId(99);
        $this->assertEquals(99, $service->getScopeColonyId());

        $this->setExpectedException('Nouron\Service\Exception');
        $service->setScopeColonyId('abc');
    }

    public function getBuildings()
    {
        $resultset = $this->_service->getBuildings();
        $this->assertInstanceOf('Nouron\Model\ResultSet', $resultset);
        $this->assertInstanceOf('Techtree\Entity\Research', $resultset->current());
        $this->markTestIncomplete();
    }

    public function getResearches()
    {
        $resultset = $this->_service->getResearches();
        $this->assertInstanceOf('Nouron\Model\ResultSet', $resultset);
        $this->assertInstanceOf('Techtree\Entity\Research', $resultset->current());
        $this->markTestIncomplete();
    }

    public function getShips()
    {
        $resultset = $this->_service->getShips();
        $this->assertInstanceOf('Nouron\Model\ResultSet', $resultset);
        $this->assertInstanceOf('Techtree\Entity\Ship', $resultset->current());
        $this->markTestIncomplete();
    }

    public function getPersonell()
    {
        $resultset = $this->_service->getPersonell();
        $this->assertInstanceOf('Nouron\Model\ResultSet', $resultset);
        $this->assertInstanceOf('Techtree\EntitPersonell', $resultset->current());
        $this->markTestIncomplete();
    }

    public function testGetTechtree()
    {
        $techtree = $this->_service->getTechtree();
        $this->assertTrue(is_array($techtree));
        $this->assertArrayHasKey('building', $techtree);
        $this->assertArrayHasKey('research', $techtree);
        $this->assertArrayHasKey('personell', $techtree);
        $this->assertArrayHasKey('ship', $techtree);
        #$this->markTestIncomplete();
    }

}