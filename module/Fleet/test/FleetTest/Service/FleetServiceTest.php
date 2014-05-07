<?php
namespace FleetTest\Service;

use NouronTest\Service\AbstractServiceTest;
use Fleet\Service\FleetService;

use Fleet\Table\FleetTable;
use Fleet\Table\FleetShipTable;
use Fleet\Table\FleetPersonellTable;
use Fleet\Table\FleetResearchTable;
use Fleet\Table\FleetOrderTable;
use Fleet\Table\FleetResourceTable;

use Fleet\Entity\Fleet;
use Fleet\Entity\FleetShip;
use Fleet\Entity\FleetPersonell;
use Fleet\Entity\FleetResearch;
use Fleet\Entity\FleetOrder;
use Fleet\Entity\FleetResource;


class FleetServiceTest extends AbstractServiceTest
{
    public function setUp()
    {
        $this->initDatabaseAdapter();
        $this->initDatabase();

        $tables = array();
        $tables['fleet']  = new FleetTable($this->dbAdapter, new Fleet());
        $tables['fleetship'] = new FleetShipTable($this->dbAdapter, new FleetShip());
        $tables['fleetpersonell'] = new FleetPersonellTable($this->dbAdapter, new FleetPersonell());
        $tables['fleetresearch']  = new FleetResearchTable($this->dbAdapter, new FleetResearch());
        $tables['fleetorder']     = new FleetOrderTable($this->dbAdapter, new FleetOrder());
        $tables['fleetresource']  = new FleetResourceTable($this->dbAdapter, new FleetResource());

        $tables['personell'] = new \Techtree\Table\PersonellTable($this->dbAdapter, new \Techtree\Entity\Personell());
        $tables['research']  = new \Techtree\Table\ResearchTable($this->dbAdapter, new \Techtree\Entity\Research());
        $tables['ship']  = new \Techtree\Table\ShipTable($this->dbAdapter, new \Techtree\Entity\Ship());
        $tables['colony'] = new \Galaxy\Table\ColonyTable($this->dbAdapter, new \Galaxy\Entity\Colony());
        $tables['system'] = new \Galaxy\Table\SystemTable($this->dbAdapter, new \Galaxy\Entity\System());
        $tables['colonyship']      = new \Techtree\Table\ColonyShipTable($this->dbAdapter, new \Techtree\Entity\ColonyShip());
        $tables['colonypersonell'] = new \Techtree\Table\ColonyPersonellTable($this->dbAdapter, new \Techtree\Entity\ColonyPersonell());
        $tables['colonyresearch']  = new \Techtree\Table\ColonyResearchTable($this->dbAdapter, new \Techtree\Entity\ColonyResearch());
        $tables['colonyresource']  = new \Resources\Table\ColonyTable($this->dbAdapter, new \Resources\Entity\Resource());

        $tick = new \Nouron\Service\Tick(1234);
        #$tick->setTickCount(1234);

        $this->_service = new FleetService($tick, $tables);

        $this->fleetId = 10;
        $this->shipId = 29;
        $this->researchId = 33;
    }

    public function testGetFleet()
    {
        $result = $this->_service->getFleet(8);
        $this->assertInstanceOf('Fleet\Entity\Fleet', $result);
        $result = $this->_service->getFleet(1);
        $this->assertFalse($result);
    }

    public function testSaveFleet()
    {
        $this->markTestIncomplete();
    }

    public function testSaveFleetOrder()
    {
        $this->markTestIncomplete();
    }

    public function testGetFleetOrdersByFleetIds()
    {
        $this->markTestIncomplete();
    }

    public function testAddOrder()
    {
        $this->markTestIncomplete();
    }

    public function testTransferShip()
    {
        $this->markTestIncomplete();
    }

    public function testTransferResearch()
    {
        $this->markTestIncomplete();
    }

    public function testTransferPersonell()
    {
        $this->markTestIncomplete();
    }

    public function testTransferTechnology()
    {
        $this->markTestIncomplete();
    }

    public function testTransferResource()
    {
        $this->markTestIncomplete();
    }

    public function testGetFleetShip()
    {
        $result = $this->_service->getFleetShip(array('fleet_id' => $this->fleetId, 'ship_id' => $this->shipId));
        $this->assertInstanceOf('Fleet\Entity\FleetShip', $result);
        $result = $this->_service->getFleetShip(array('fleet_id' => $this->fleetId, 'ship_id' => 0));
        $this->assertFalse($result);
        $result = $this->_service->getFleetShip(array('fleet_id' => $this->fleetId, 'ship_id' => 0), true);
        $this->assertInstanceOf('Fleet\Entity\FleetShip', $result);
    }

    public function testGetFleetResearch()
    {
        $result = $this->_service->getFleetResearch(array('fleet_id' => $this->fleetId, 'research_id' => $this->researchId));
        $this->assertInstanceOf('Fleet\Entity\FleetResearch', $result);
        $result = $this->_service->getFleetResearch(array('fleet_id' => $this->fleetId, 'research_id' => 0));
        $this->assertFalse($result);
        $result = $this->_service->getFleetResearch(array('fleet_id' => $this->fleetId, 'research_id' => 0), true);
        $this->assertInstanceOf('Fleet\Entity\FleetResearch', $result);
        $this->markTestIncomplete();
    }

    public function testGetFleetShips()
    {
        $result = $this->_service->getFleetShips(array('fleet_id' => $this->fleetId));
        $this->assertInstanceOf('Nouron\Model\ResultSet', $result);
        $this->assertTrue($result->count() == 5);
    }

    public function testGetFleetShipsByFleetId()
    {
        $result = $this->_service->getFleetShipsByFleetId($this->fleetId);
        $this->assertInstanceOf('Nouron\Model\ResultSet', $result);
        $this->assertTrue($result->count() == 5);
    }

    public function testGetFleetResearches()
    {
        $result = $this->_service->getFleetResearches(array('fleet_id' => $this->fleetId));
        $this->assertInstanceOf('Nouron\Model\ResultSet', $result);
        $this->assertTrue($result->count() == 8);
    }

    public function testGetFleetResearchesByFleetId()
    {
        $result = $this->_service->getFleetResearchesByFleetId($this->fleetId);
        $this->assertInstanceOf('Nouron\Model\ResultSet', $result);
        $this->assertTrue($result->count() == 8);
    }

    public function testGetFleetPersonell()
    {
        $result = $this->_service->getFleetPersonell(array('fleet_id' => $this->fleetId));
        $this->assertInstanceOf('Nouron\Model\ResultSet', $result);
        $this->assertTrue($result->count() == 3);
    }

    public function testGetFleetPersonellByFleetId()
    {
        $result = $this->_service->getFleetPersonellByFleetId($this->fleetId);
        $this->assertInstanceOf('Nouron\Model\ResultSet', $result);
        $this->assertTrue($result->count() == 3);
    }

    public function testGetFleetResources()
    {
        $result = $this->_service->getFleetResources(array('fleet_id' => $this->fleetId));
        $this->assertInstanceOf('Nouron\Model\ResultSet', $result);
        $this->assertTrue($result->count() == 6);
    }

    public function testGetFleetResourcesByFleetId()
    {
        $result = $this->_service->getFleetResourcesByFleetId($this->fleetId);
        $this->assertInstanceOf('Nouron\Model\ResultSet', $result);
        $this->assertTrue($result->count() == 6);
    }

    public function testGetFleetResource()
    {
        $this->markTestIncomplete();
    }

    public function testGetOrders()
    {
        $this->markTestIncomplete();
    }

    public function testGetFleetsByUserId()
    {
        $this->markTestIncomplete();
    }

    public function testGetFleetsByEntityId()
    {

        $this->markTestIncomplete();
    }

    public function testGetFleetTechnologies()
    {
        $result = $this->_service->getFleetTechnologies($this->fleetId);
        $this->assertTrue(is_array($result));
        $this->assertArrayHasKey('research', $result);
        $this->assertArrayHasKey('personell', $result);
        $this->assertArrayHasKey('ship', $result);
    }
}