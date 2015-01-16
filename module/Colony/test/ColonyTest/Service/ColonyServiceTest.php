<?php
namespace ColonyTest\Service;

use CoreTest\Service\AbstractServiceTest;
use Colony\Entity\Colony;
use Colony\Table\ColonyTable;
use Colony\Service\ColonyService;
use Zend\Session\Container;

class ColonyServiceTest extends AbstractServiceTest
{
    public function setUp()
    {
        $this->initDatabaseAdapter();

        #$this->initDatabase();

        $tables = array();
        $tables['colony'] = new ColonyTable($this->dbAdapter, new Colony());
        $tables['colonybuilding'] = new \Techtree\Table\ColonyBuildingTable($this->dbAdapter, new \Techtree\Entity\ColonyBuilding());
        $tables['colonyresource'] = new \Resources\Table\ColonyTable($this->dbAdapter, new \Resources\Entity\Colony());
        $tables['systemobject']   = new \Galaxy\Table\SystemObjectTable($this->dbAdapter, new \Galaxy\Entity\SystemObject());

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


        //$gateways['techtree'] = $serviceLocator->get('Techtree\Service\BuildingService'); // causes circularDependancyException
        $this->_service = new ColonyService($tick, $tables, array(), $config);
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

    public function testGetColonies()
    {
        $objects = $this->_service->getColonies();
        $this->assertInstanceOf('Core\Model\ResultSet', $objects);
        $this->assertInstanceOf('Colony\Entity\Colony', $objects->current());
    }

    public function testGetColony()
    {
        $object = $this->_service->getColony($this->colonyId);
        $this->assertInstanceOf('Colony\Entity\Colony', $object);
        $object = $this->_service->getColony(99);
        $this->assertFalse($object);

        $this->setExpectedException('Core\Service\Exception');
        $objects = $this->_service->getColony(-1);
    }

    public function testGetColoniesByUserId()
    {
        $objects = $this->_service->getColoniesByUserId($this->userId);
        $this->assertInstanceOf('Core\Model\ResultSet', $objects);
        $this->assertInstanceOf('Colony\Entity\Colony', $objects->current());

        $objects = $this->_service->getColoniesByUserId(99);
        $this->assertInstanceOf('Core\Model\ResultSet', $objects);
        $this->assertFalse($objects->current());

        $this->setExpectedException('Core\Service\Exception');
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

        #$this->markTestIncomplete();

    }

    public function testGetPrimeColony()
    {
        $object = $this->_service->getPrimeColony($this->userId);
        $this->assertInstanceOf('Colony\Entity\Colony', $object);
        $this->assertTrue($object->getIsPrimary());

        $object = $this->_service->getPrimeColony(0);
        $this->assertInstanceOf('Colony\Entity\Colony', $object);
        $this->assertTrue($object->getIsPrimary());


        $this->setExpectedException('Core\Service\Exception');
        $this->_service->getPrimeColony(19);
    }

    public function testSetActiveColony()
    {
        $session = new Container('activeIds');

        // first test a successfull
        $session->colonyId = null;
        $session->userId = $this->userId;

        $this->assertNull($session->colonyId);
        $this->_service->setActiveColony(1);
        $this->assertEquals(1, $session->colonyId);

        // second: test a fail
        $session->colonyId = null;
        $session->userId = 19; // colony does not belong to user

        $this->assertNull($session->colonyId);
        $this->_service->setActiveColony(1);
        $this->assertNull($session->colonyId);

    }

    public function testSetSelectedColony()
    {
        $session = new Container('selectedIds');
        $this->assertNull($session->colonyId);

        $this->_service->setSelectedColony(1);
        $this->assertEquals(1, $session->colonyId);

        $this->_service->setSelectedColony(2);
        $this->assertEquals(2, $session->colonyId);

    }

    public function testGetColoniesByCoords()
    {
        $x = 6800;
        $y = 3000;
        $results = $this->_service->getColoniesByCoords(array($x, $y));
        $this->assertInstanceOf('Core\Model\ResultSet', $results);
        $this->markTestIncomplete();
    }

    public function testGetColonyByCoords()
    {
        // test positive
        $coords = array(6828, 3016, 1);
        $object = $this->_service->getColonyByCoords($coords);
        $this->assertInstanceOf('Colony\Entity\Colony', $object);
        $this->assertEquals(1, $object->getId());

        // test negative
        $coords = array(9190, 7790, 99); // system object exists but no colony!
        $object = $this->_service->getColonyByCoords($coords);
        $this->assertFalse($object);

        // test exception
        $this->setExpectedException('Core\Service\Exception');
        $coords = array('a', 'b');
        $this->_service->getColonyByCoords($coords);
    }

    public function testGetColoniesBySystemObjectId()
    {
        // test positive
        $objects = $this->_service->getColoniesBySystemObjectId($this->planetaryId);
        $this->assertInstanceOf('Core\Model\ResultSet', $objects);
        $this->assertInstanceOf('Colony\Entity\Colony', $objects->current());

        // test negative
        $objects = $this->_service->getColoniesBySystemObjectId(99);
        $this->assertInstanceOf('Core\Model\ResultSet', $objects);
        $this->assertFalse($objects->current());

        // test exception
        $this->setExpectedException('Core\Service\Exception');
        $this->_service->getColoniesBySystemObjectId('a');
    }

}