<?php
namespace TechtreeTest\Service;

use NouronTest\Service\AbstractServiceTest;
use Techtree\Service\ColonyService;
use Techtree\Table\BuildingTable;
use Techtree\Table\BuildingCostTable;
use Techtree\Table\ColonyBuildingTable;
use Techtree\Table\PersonellTable;
use Techtree\Entity\Building;
use Techtree\Entity\BuildingCost;
use Techtree\Entity\ColonyBuilding;
use Techtree\Entity\Personell;

class ColonyServiceTest extends AbstractServiceTest
{
    /**
     * @var integer
     */
    private $_colony_id = null;

    public function setUp()
    {
        $this->initDatabaseAdapter();

        // default test parameters
        $this->_entityId = 27;
        $this->_colonyId = 1;

        $tables = array();
        $tables['buildings'] = new BuildingTable($this->dbAdapter, new Building());
        $tables['building_costs']   = new BuildingCostTable($this->dbAdapter, new BuildingCost());
        $tables['colony_buildings'] = new ColonyBuildingTable($this->dbAdapter, new ColonyBuilding());
        #$tables['personell'] = new PersonellTable($this->dbAdapter, new Personell());

        $tick = new \Nouron\Service\Tick(1234);
        #$tick->setTickCount(1234);

        $services = array();
        $services['resources'] = $this->getMockBuilder('Resources\Service\ResourcesService')
                                      ->disableOriginalConstructor()
                                      ->getMock();

        $services['galaxy']    = $this->getMockBuilder('Galaxy\Service\Gateway')
                                      ->disableOriginalConstructor()
                                      ->getMock();

        $this->_service = new ColonyService($tick, $tables, $services);
        $logger = $this->getMockBuilder('Zend\Log\Logger')
                       ->disableOriginalConstructor()
                       ->getMock();
        $this->_service->setLogger($logger);
    }

    /**
     * @param integer
     */
    public function setColonyId($id)
    {
        $this->markTestIncomplete();
    }

    /**
     * @return integer
     */
    public function getColonyId() {
        $this->markTestIncomplete();
    }

    /**
     * @return ResultSet
     */
    public function getBuildings() {
        $this->markTestIncomplete();
    }

    /**
     * @return ResultSet
     */
    public function getResearches() {
        $this->markTestIncomplete();
    }

    /**
     * @return ResultSet
     */
    public function getShips() {
        $this->markTestIncomplete();
    }

    /**
     * @return ResultSet
     */
    public function getPersonell() {
        $this->markTestIncomplete();
    }

    /**
     *
     * @return array
     */
    public function testGetTechtree()
    {
        #$techtree = $this->_service->getTechtree();
        #$this->assertTrue(is_array($techtree));
        #$this->assertArrayHasKey('building', $techtree);
        #$this->assertArrayHasKey('research', $techtree);
        #$this->assertArrayHasKey('personell', $techtree);
        #$this->assertArrayHasKey('ship', $techtree);
        $this->markTestIncomplete();
    }

}