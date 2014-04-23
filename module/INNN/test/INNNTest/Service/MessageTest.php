<?php
namespace INNNTest\Service;

use PHPUnit_Framework_TestCase;
use NouronTest\Service\AbstractServiceTest;
use INNN\Service\Message as MessageService;
use INNN\Table\MessageTable;
use INNN\Table\MessageView;
use INNN\Entity\Message;
#use User\Table\UserTable;
#use User\Entity\User;

class MessageTest extends AbstractServiceTest
{
    public function setUp()
    {
        $this->initDatabaseAdapter();
        $this->initDatabase();

        $tables = array();
        $tables['message'] = new MessageTable($this->dbAdapter, new \INNN\Entity\Message());
        $tables['message_view'] = new MessageView($this->dbAdapter, new \INNN\Entity\Message());
        #$tables['user'] = new UserTable($dbAdapter, new User());

        $tick = new \Nouron\Service\Tick(1234);
        #$tick->setTickCount(1234);

        $this->_service = new MessageService($tick, $tables);

        // default test parameters
        $this->_messageId = 22;
        $this->_userA_Id = 0;
        $this->_userB_Id = 3;
    }

    public function testGetMessage()
    {
        // test positive
        $object = $this->_service->getMessage($this->_messageId);
        $this->assertEquals('INNN\Entity\Message', get_class($object));
        $this->assertEquals($this->_messageId, $object->getId());

        // test negative
        $object = $this->_service->getMessage(99);
        $this->assertFalse($object);

        // test exception
        $this->setExpectedException('Nouron\Service\Exception');
        $object = $this->_service->getMessage(null);

    }

    public function testGetInboxMessages()
    {
        // test positive
        $object = $this->_service->getInboxMessages($this->_userA_Id);
        $this->assertEquals('Nouron\Model\ResultSet', get_class($object));
        $results = $object->getArrayCopy();
        $this->assertTrue(is_array($results));
        $this->assertEquals(2, count($results) );
        #$this->markTestIncomplete();

        // test negative
        $object = $this->_service->getInboxMessages(99);
        $this->assertEquals('Nouron\Model\ResultSet', get_class($object));
        $results = $object->getArrayCopy();
        $this->assertTrue(is_array($results));
        $this->assertEquals(0, count($results) );

        // test exception
        $this->setExpectedException('Nouron\Service\Exception');
        $object = $this->_service->getInboxMessages(null);
    }

    public function testGetOutboxMessages()
    {
        // test positive
        $object = $this->_service->getOutboxMessages($this->_userA_Id);
        $this->assertEquals('Nouron\Model\ResultSet', get_class($object));
        $results = $object->getArrayCopy();
        $this->assertTrue(is_array($results));
        $this->assertEquals(3, count($results) );
        #$this->markTestIncomplete();

        // test negative
        $object = $this->_service->getOutboxMessages(99);
        $this->assertEquals('Nouron\Model\ResultSet', get_class($object));
        $results = $object->getArrayCopy();
        $this->assertTrue(is_array($results));
        $this->assertEquals(0, count($results) );

        // test exception
        $this->setExpectedException('Nouron\Service\Exception');
        $object = $this->_service->getOutboxMessages(null);
    }

    public function testGetArchivedMessages()
    {
        // test positive
        $object = $this->_service->getArchivedMessages($this->_userB_Id);
        $this->assertEquals('Nouron\Model\ResultSet', get_class($object));
        $results = $object->getArrayCopy();
        $this->assertTrue(is_array($results));
        $this->assertEquals(1, count($results) );
        #$this->markTestIncomplete();

        // test negative
        $object = $this->_service->getArchivedMessages(99);
        $this->assertEquals('Nouron\Model\ResultSet', get_class($object));
        $results = $object->getArrayCopy();
        $this->assertTrue(is_array($results));
        $this->assertEquals(0, count($results) );

        // test exception
        $this->setExpectedException('Nouron\Service\Exception');
        $object = $this->_service->getArchivedMessages(null);
    }

    public function testSendMessage()
    {
        $entity['sender_id'] = $this->_userA_Id;
        $entity['recipient_id'] = $this->_userA_Id;
        $entity['attitude'] = 'mood_friendly';
        $entity['subject'] = 'test';
        $entity['text'] = 'test';

        $this->markTestIncomplete();

    }


    public function testSetMessageStatus()
    {
        $this->markTestIncomplete();
    }

}