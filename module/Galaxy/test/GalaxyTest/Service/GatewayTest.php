<?php
namespace GalaxyTest\Service;

use NouronTest\Service\AbstractServiceTest;
use Galaxy\Entity\Colony;
use Galaxy\Entity\System;
use Galaxy\Entity\SystemObject;
use Fleet\Entity\Fleet;
use Fleet\Entity\FleetShip;
use Fleet\Entity\FleetPersonell;
use Fleet\Entity\FleetResearch;
use Fleet\Entity\FleetResource;
use Fleet\Entity\FleetOrder;
use Galaxy\Table\ColonyTable;
use Galaxy\Table\SystemTable;
use Galaxy\Table\SystemObjectTable;
use Fleet\Table\FleetTable;
use Fleet\Table\FleetShipTable;
use Fleet\Table\FleetPersonellTable;
use Fleet\Table\FleetResearchTable;
use Fleet\Table\FleetResourceTable;
use Fleet\Table\FleetOrderTable;

use Galaxy\Service\Gateway;

class GatewayTest extends AbstractServiceTest
{
    public function setUp()
    {
        $this->initDatabaseAdapter();

        #$this->initDatabase();

        $tables = array();
        $tables['colony'] = new ColonyTable($this->dbAdapter, new Colony());
        $tables['system'] = new SystemTable($this->dbAdapter, new System());
        $tables['fleet']  = new FleetTable($this->dbAdapter, new Fleet());
        $tables['systemobject']     = new SystemObjectTable($this->dbAdapter, new SystemObject());
        $tables['fleetship']        = new FleetShipTable($this->dbAdapter, new FleetShip());
        $tables['fleetpersonell']   = new FleetPersonellTable($this->dbAdapter, new FleetPersonell());
        $tables['fleetresearch']    = new FleetResearchTable($this->dbAdapter, new FleetResearch());
        $tables['fleetorder']       = new FleetOrderTable($this->dbAdapter, new FleetOrder());
        $tables['fleetresource']    = new FleetResourceTable($this->dbAdapter, new FleetResource());
        $tables['colonybuilding']   = new \Techtree\Table\ColonyBuildingTable($this->dbAdapter, new \Techtree\Entity\ColonyBuilding());
        $tables['colonyresource']   = new \Resources\Table\ColonyTable($this->dbAdapter, new \Resources\Entity\Colony());

        $tick = new \Nouron\Service\Tick(1234);
        #$tick->setTickCount(1234);

        //$gateways['techtree'] = $serviceLocator->get('Techtree\Service\BuildingService'); // causes circularDependancyException
        $this->_gateway = new Gateway($tick, $tables, array());
        $logger = $this->getMockBuilder('Zend\Log\Logger')
                       ->disableOriginalConstructor()
                       ->getMock();
        $this->_gateway->setLogger($logger);

    }

    public function testGatewayInitialState()
    {
        $this->markTestSkipped();
    }

    public function testGetSystems()
    {
        $this->markTestSkipped();
    }

    public function testGetColonies()
    {
        $this->markTestSkipped();
    }

    public function testGetColony()
    {
        $this->markTestSkipped();
    }

    public function testGetColoniesByUserId()
    {
        $this->markTestSkipped();
    }

    public function testCheckColonyOwner()
    {
        $this->markTestSkipped();
    }

    public function testGetPrimeColony()
    {
        $this->markTestSkipped();
    }

    public function testSwitchCurrentColony()
    {
        $this->markTestSkipped();
    }

    public function testGetByCoordinates()
    {
        $this->markTestSkipped();
    }

    public function testGetSystem()
    {
        $this->markTestSkipped();
    }

    public function testGetSystemObjects()
    {
        $this->markTestSkipped();
    }

    public function testGetSystemByPlanetary()
    {
        $this->markTestSkipped();
    }

    public function testGetSystemBySystemObject()
    {
        $this->markTestSkipped();
    }

    public function testGetSystemObject()
    {
        $this->markTestSkipped();
    }

    public function testGetSystemObjectByColonyId()
    {
        $this->markTestSkipped();
    }

    public function testGetDistance()
    {
        $this->markTestSkipped();
    }

    public function testGetDistanceTicks()
    {
        $this->markTestSkipped();
    }

    public function testGetPath()
    {
        $this->markTestSkipped();
    }

    public function testGetColonyResource()
    {
        $this->markTestSkipped();
    }

    public function testGetSystemObjectByCoords()
    {
        $this->markTestSkipped();
    }

    public function testGetColonyByCoords()
    {
        $this->markTestSkipped();
    }

    public function testGetColoniesBySystemObjectId()
    {
        $this->markTestSkipped();
    }

    public function testGetOrders()
    {
        $this->markTestSkipped();
    }
}