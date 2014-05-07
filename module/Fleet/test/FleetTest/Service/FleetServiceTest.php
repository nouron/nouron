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


        $tables['colony'] = new \Galaxy\Table\ColonyTable($this->dbAdapter, new \Galaxy\Entity\Colony());
        $tables['system'] = new \Galaxy\Table\SystemTable($this->dbAdapter, new \Galaxy\Entity\System());
        $tables['colonyship']      = new \Techtree\Table\ColonyShipTable($this->dbAdapter, new \Techtree\Entity\ColonyShip());
        $tables['colonypersonell'] = new \Techtree\Table\ColonyPersonellTable($this->dbAdapter, new \Techtree\Entity\ColonyPersonell());
        $tables['colonyresearch']  = new \Techtree\Table\ColonyResearchTable($this->dbAdapter, new \Techtree\Entity\ColonyResearch());
        $tables['colonyresource']  = new \Resources\Table\ColonyTable($this->dbAdapter, new \Resources\Entity\Resource());

        $tick = new \Nouron\Service\Tick(1234);
        #$tick->setTickCount(1234);

        $this->_service = new FleetService($tick, $tables);

    }

    public function testGetFleet()
    {
        $this->markTestIncomplete();
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
        $this->markTestIncomplete();
    }

    public function testGetFleetResearch()
    {
        $this->markTestIncomplete();
    }

    public function testGetFleetShips()
    {
        $this->markTestIncomplete();
    }

    public function testGetFleetShipsByFleetId()
    {
        $this->markTestIncomplete();
    }

    public function testGetFleetResearches()
    {
        $this->markTestIncomplete();
    }

    public function testGetFleetResearchesByFleetId()
    {
        $this->markTestIncomplete();
    }

    public function testGetFleetPersonell()
    {
        $this->markTestIncomplete();
    }

    public function testGetFleetPersonellByFleetId()
    {
        $this->markTestIncomplete();
    }

    public function testGetFleetResources()
    {
        $this->markTestIncomplete();
    }

    public function testGetFleetResourcesByFleetId()
    {
        $this->markTestIncomplete();
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
        $this->markTestIncomplete();
    }
}