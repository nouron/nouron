<?php
namespace INNNTest\Service;

use CoreTest\Service\AbstractServiceTest;
use INNN\Service\EventService;
use INNN\Table\EventTable;
use INNN\Table\EventView;
use INNN\Entity\Event;
#use User\Table\UserTable;
#use User\Entity\User;

class EventServiceTest extends AbstractServiceTest
{
    public function setUp()
    {
        $this->initDatabaseAdapter();
        $this->initDatabase();

        $tables = array();
        $tables['event'] = new EventTable($this->dbAdapter, new Event());
        #$tables['user'] = new UserTable($dbAdapter, new User());

        $tick = new \Core\Service\Tick(1234);
        #$tick->setTickCount(1234);

        $this->_service = new EventService($tick, $tables);

        // default test parameters
        $this->_eventId = 16;
        $this->_userA_Id = 0;
        $this->_userB_Id = 3;
    }

    public function testGetEvent()
    {
        // test positive
        $object = $this->_service->getEvent($this->_eventId);
        $this->assertInstanceOf('INNN\Entity\Event', $object);
        $this->assertEquals($this->_eventId, $object->getId());

        // test negative
        $object = $this->_service->getEvent(99);
        $this->assertFalse($object);

        // test exception
        $this->setExpectedException('Core\Service\Exception');
        $this->_service->getEvent(null);

        #$this->markTestIncomplete();
    }

    public function testGetEvents()
    {
        $objects = $this->_service->getEvents($this->_userA_Id);
        $this->assertInstanceOf("Core\Model\ResultSet", $objects);
        $this->assertEquals(0, $objects->count());

        $objects = $this->_service->getEvents($this->_userB_Id);
        $this->assertInstanceOf("Core\Model\ResultSet", $objects);
        $this->assertEquals(2, $objects->count());
    }

    public function testCreateEvent()
    {
        $entity = array(
            'user' => 3,
            'tick' => 12345,
            'event' => 'techtree.level_up_finished',
            'area' => 'colony',
            'parameters' => '{"colony_id":0,"tech_id":27}'
        );

        $primaryKey = $this->_service->createEvent($entity);
        $this->assertTrue(is_integer($primaryKey));


    }
}
