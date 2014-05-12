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

        # TODO: temporary solution, dirty => make it better; load real config
        $config = array(
            'galaxy_view_config' => array(
                'range'  => 10000,
                'offset' => 0,
                'scale'  => 0.05,
                'systemSize' => 3
            ),
            'system_view_config' => array(
                'range'  => 100,
                'offset' => 100,
                'scale'  => 10,//15,
                'planetSize' => 10,//20,
                'slotSize' => 10,//25
            )
        );


        //$gateways['techtree'] = $serviceLocator->get('Techtree\Service\BuildingService'); // causes circularDependancyException
        $this->_service = new Gateway($tick, $tables, array(), $config);
        $logger = $this->getMockBuilder('Zend\Log\Logger')
                       ->disableOriginalConstructor()
                       ->getMock();
        $this->_service->setLogger($logger);

        $this->systemId = 1;
        $this->planetaryId = 1;
        $this->colonyId = 1;
        $this->userId = 3;

    }

    public function testGatewayInitialState()
    {
        $this->markTestSkipped();
    }

    public function testGetSystems()
    {
        $objects = $this->_service->getSystems();
        $this->assertInstanceOf('Nouron\Model\ResultSet', $objects);
        $this->assertInstanceOf('Galaxy\Entity\System', $objects->current());
    }

    public function testGetColonies()
    {
        $objects = $this->_service->getColonies();
        $this->assertInstanceOf('Nouron\Model\ResultSet', $objects);
        $this->assertInstanceOf('Galaxy\Entity\Colony', $objects->current());
    }

    public function testGetColony()
    {
        $object = $this->_service->getColony($this->colonyId);
        $this->assertInstanceOf('Galaxy\Entity\Colony', $object);
        $object = $this->_service->getColony(99);
        $this->assertFalse($object);

        $this->setExpectedException('Nouron\Service\Exception');
        $objects = $this->_service->getColony(-1);
    }

    public function testGetColoniesByUserId()
    {
        $objects = $this->_service->getColoniesByUserId($this->userId);
        $this->assertInstanceOf('Nouron\Model\ResultSet', $objects);
        $this->assertInstanceOf('Galaxy\Entity\Colony', $objects->current());

        $objects = $this->_service->getColoniesByUserId(99);
        $this->assertInstanceOf('Nouron\Model\ResultSet', $objects);
        $this->assertFalse($objects->current());

        $this->setExpectedException('Nouron\Service\Exception');
        $objects = $this->_service->getColoniesByUserId(-1);
    }

    public function testCheckColonyOwner()
    {
        $check = $this->_service->checkColonyOwner($this->colonyId, $this->userId);
        $this->assertTrue($check);

        $check = $this->_service->checkColonyOwner(99, $this->userId);
        $this->assertFalse($check);

        $check = $this->_service->checkColonyOwner($this->colonyId, 99);
        $this->assertFalse($check);

        $this->markTestIncomplete();

    }

    public function testGetPrimeColony()
    {
        $object = $this->_service->getPrimeColony($this->userId);
        $this->assertInstanceOf('Galaxy\Entity\Colony', $object);
        $this->assertTrue($object->getIsPrimary());
    }

    public function testSwitchCurrentColony()
    {
        $this->markTestSkipped();
    }

    public function testGetByCoordinates()
    {
        $results = $this->_service->getByCoordinates('fleets', array(6800,3000));
        $this->assertInstanceOf('Nouron\Model\ResultSet', $results);
        $results = $this->_service->getByCoordinates('colonies', array(6800,3000));
        $this->assertInstanceOf('Nouron\Model\ResultSet', $results);
        $results = $this->_service->getByCoordinates('objects', array(6800,3000));
        $this->assertInstanceOf('Nouron\Model\ResultSet', $results);
        $this->markTestIncomplete();
    }

    public function testGetSystem()
    {
        $object = $this->_service->getSystem($this->systemId);
        $this->assertInstanceOf('Galaxy\Entity\System', $object);

        $object = $this->_service->getSystem(99);
        $this->assertFalse($object);

        $this->setExpectedException('Nouron\Service\Exception');
        $this->_service->getSystem(-1);
    }

    public function testGetSystemObjects()
    {
        $objects = $this->_service->getSystemObjects($this->systemId);
        $this->assertInstanceOf('Nouron\Model\ResultSet', $objects);
        $this->assertInstanceOf('Galaxy\Entity\SystemObject', $objects->current());
    }

    public function testGetSystemByPlanetary()
    {
        $object = $this->_service->getSystemByPlanetary($this->planetaryId);
        $this->assertInstanceOf('Galaxy\Entity\System', $object);

        $object = $this->_service->getSystemByPlanetary(99);
        $this->assertFalse($object);

        $this->setExpectedException('Nouron\Service\Exception');
        $this->_service->getSystemByPlanetary(-1);

        $this->markTestIncomplete();
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