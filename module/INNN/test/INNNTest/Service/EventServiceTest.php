<?php
namespace INNNTest\Service;

use NouronTest\Service\AbstractServiceTest;
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

        $tick = new \Nouron\Service\Tick(1234);
        #$tick->setTickCount(1234);

        $this->_service = new EventService($tick, $tables);

        // default test parameters
        $this->_eventId = 22; // TODO: test existing event id
        $this->_userA_Id = 0;
        $this->_userB_Id = 3;
    }

    public function testGetEvent()
    {
        // test positive
        $object = $this->_service->getEvent($this->_eventId);
        #$this->assertEquals('INNN\Entity\Event', get_class($object));
        #$this->assertEquals($this->_eventId, $object->getId());

        // test negative
        $object = $this->_service->getEvent(99);
        $this->assertFalse($object);

        // test exception
        $this->setExpectedException('Nouron\Service\Exception');
        $this->_service->getEvent(null);

        $this->markTestIncomplete();
    }
}
