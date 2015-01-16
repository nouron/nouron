<?php
namespace GalaxyTest\Service;

use CoreTest\Service\AbstractServiceTest;
use Galaxy\Entity\System;
use Galaxy\Entity\SystemObject;
use Galaxy\Table\SystemTable;
use Galaxy\Table\SystemObjectTable;
use Galaxy\Service\Gateway;

use Colony\Entity\Colony;
use Colony\Table\ColonyTable;

use Fleet\Entity\Fleet;
use Fleet\Entity\FleetShip;
use Fleet\Entity\FleetPersonell;
use Fleet\Entity\FleetResearch;
use Fleet\Entity\FleetResource;
use Fleet\Entity\FleetOrder;
use Fleet\Table\FleetTable;
use Fleet\Table\FleetShipTable;
use Fleet\Table\FleetPersonellTable;
use Fleet\Table\FleetResearchTable;
use Fleet\Table\FleetResourceTable;
use Fleet\Table\FleetOrderTable;

use Zend\Session\Container;

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

        $tick = new \Core\Service\Tick(1234);
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

        $services = array();

        //$gateways['techtree'] = $serviceLocator->get('Techtree\Service\BuildingService'); // causes circularDependancyException
        $this->_service = new Gateway($tick, $tables, $services, $config);
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
        $this->assertInstanceOf('Core\Model\ResultSet', $objects);
        $this->assertInstanceOf('Galaxy\Entity\System', $objects->current());
    }

    public function testGetByCoordinates()
    {
        $x = 6800;
        $y = 3000;
        $results = $this->_service->getByCoordinates('fleets', array($x, $y));
        $this->assertInstanceOf('Core\Model\ResultSet', $results);
        $results = $this->_service->getByCoordinates('colonies', array($x, $y));
        $this->assertInstanceOf('Core\Model\ResultSet', $results);
        $results = $this->_service->getByCoordinates('objects', array($x, $y));
        $this->assertInstanceOf('Core\Model\ResultSet', $results);
        $this->markTestIncomplete();
    }

    public function testGetSystem()
    {
        $object = $this->_service->getSystem($this->systemId);
        $this->assertInstanceOf('Galaxy\Entity\System', $object);

        $object = $this->_service->getSystem(99);
        $this->assertFalse($object);

        $this->setExpectedException('Core\Service\Exception');
        $this->_service->getSystem(-1);
    }

    public function testGetSystemObject()
    {
        $object = $this->_service->getSystemObject($this->planetaryId);
        $this->assertInstanceOf('Galaxy\Entity\SystemObject', $object);

        $this->setExpectedException('Exception');
        $object = $this->_service->getSystemObject(-1);
    }

    public function testGetSystemObjects()
    {
        $objects = $this->_service->getSystemObjects($this->systemId);
        $this->assertInstanceOf('Core\Model\ResultSet', $objects);
        $this->assertInstanceOf('Galaxy\Entity\SystemObject', $objects->current());
    }

    public function testGetSystemByPlanetary()
    {
        $object = $this->_service->getSystemByPlanetary($this->planetaryId);
        $this->assertInstanceOf('Galaxy\Entity\System', $object);

        #$object = $this->_service->getSystemByPlanetary(99);
        #$this->assertFalse($object);

        $this->setExpectedException('Core\Service\Exception');
        $this->_service->getSystemByPlanetary(-1);

        $this->markTestIncomplete();
    }

    public function testGetSystemBySystemObject()
    {
        $system = $this->_service->getSystemBySystemObject(1);
        $this->assertInstanceOf('Galaxy\Entity\System', $system);
        $this->assertEquals(1, $system->getId());

        $system = $this->_service->getSystemBySystemObject(2);
        $this->assertInstanceOf('Galaxy\Entity\System', $system);
        $this->assertEquals(1, $system->getId());

        $systemObjectId = 3;
        $systemId = 4;
        $system = $this->_service->getSystemBySystemObject($systemObjectId);
        $this->assertInstanceOf('Galaxy\Entity\System', $system);
        $this->assertEquals($systemId, $system->getId());

        $blackhole_id = 12;
        $system = $this->_service->getSystemBySystemObject($blackhole_id);
        $this->assertFalse($system);

        $this->setExpectedException('Core\Service\Exception');
        $this->_service->getSystemBySystemObject('a');

    }

    public function testGetSystemObjectByColonyId()
    {
        // expect true
        $object = $this->_service->getSystemObjectByColonyId(1);
        $this->assertInstanceOf('Galaxy\Entity\SystemObject', $object);
        $this->assertEquals(1, $object->getId());

        // expect false
        $object = $this->_service->getSystemObjectByColonyId(99);
        $this->assertFalse($object);

        // expect Exception
        $this->setExpectedException('Core\Service\Exception');
        $this->_service->getSystemObjectByColonyId('a');
    }

    public function testGetDistance()
    {
        $coordsA = array(2, 0);
        $coordsB = array(8, 1);
        $d = $this->_service->getDistance($coordsA, $coordsB);
        $this->assertEquals(7, $d);

        $coordsA = array(2, 3);
        $coordsB = array(4, 5);
        $d = $this->_service->getDistance($coordsA, $coordsB);
        $this->assertEquals(4, $d);
    }

    public function testGetDistanceTicks()
    {
        $coordsA = array(2, 0);
        $coordsB = array(8, 1);
        $d = $this->_service->getDistanceTicks($coordsA, $coordsB);
        $this->assertEquals(8, $d);

        $coordsA = array(2, 3);
        $coordsB = array(4, 5);
        $d = $this->_service->getDistanceTicks($coordsA, $coordsB);
        $this->assertEquals(5, $d);
    }

    /**
     * @dataProvider dataProviderForTestGetPath
     */
    public function testGetPath(array $coordsA, array $coordsB, $speed, $expectedWaypointCount)
    {
        $path = $this->_service->getPath($coordsA, $coordsB, $speed);
        $this->assertTrue(is_array($path));
        $this->assertEquals($expectedWaypointCount, count($path));
    }

    public function dataProviderForTestGetPath()
    {
        return array(
            array(array(2,0), array(8,1), 1, 7),
            array(array(2,0), array(8,1), 2, 4),
            array(array(0,2), array(1,8), 1, 7),
            array(array(0,2), array(1,8), 2, 4),
            array(array(0,2,1), array(1,8,1), 2, 4),
        );
        // @TODO: add more test cases!
    }

    public function testGetSystemObjectByCoords()
    {
        // test positive
        $coords = array(6828, 3016);
        $object = $this->_service->getSystemObjectByCoords($coords);
        $this->assertInstanceOf('Galaxy\Entity\SystemObject', $object);
        $this->assertEquals(1, $object->getId());

        // test negative
        $coords = array('1', -1);
        $object = $this->_service->getSystemObjectByCoords($coords);
        $this->assertFalse($object);

        // test exception
        $this->setExpectedException('Core\Service\Exception');
        $coords = array('a', 'b');
        $this->_service->getSystemObjectByCoords($coords);

    }

#    public function testGetColonyByCoords()
#    {
#        // test positive
#        $coords = array(6828, 3016, 1);
#        $object = $this->_service->getColonyByCoords($coords);
#        $this->assertInstanceOf('Galaxy\Entity\Colony', $object);
#        $this->assertEquals(1, $object->getId());
#
#        // test negative
#        $coords = array(9190, 7790, 99); // system object exists but no colony!
#        $object = $this->_service->getColonyByCoords($coords);
#        $this->assertFalse($object);
#
#        // test exception
#        $this->setExpectedException('Core\Service\Exception');
#        $coords = array('a', 'b');
#        $this->_service->getColonyByCoords($coords);
#    }
#
#    public function testGetColoniesBySystemObjectId()
#    {
#        // test positive
#        $objects = $this->_service->getColoniesBySystemObjectId($this->planetaryId);
#        $this->assertInstanceOf('Core\Model\ResultSet', $objects);
#        $this->assertInstanceOf('Galaxy\Entity\Colony', $objects->current());
#
#        // test negative
#        $objects = $this->_service->getColoniesBySystemObjectId(99);
#        $this->assertInstanceOf('Core\Model\ResultSet', $objects);
#        $this->assertFalse($objects->current());
#
#        // test exception
#        $this->setExpectedException('Core\Service\Exception');
#        $this->_service->getColoniesBySystemObjectId('a');
#    }

}