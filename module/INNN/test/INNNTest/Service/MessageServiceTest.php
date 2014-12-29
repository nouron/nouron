<?php
namespace INNNTest\Service;

use CoreTest\Service\AbstractServiceTest;
use INNN\Service\MessageService;
use INNN\Table\MessageTable;
use INNN\Table\MessageView;
use INNN\Entity\Message;
#use User\Table\UserTable;
#use User\Entity\User;

class MessageServiceTest extends AbstractServiceTest
{
    public function setUp()
    {
        $this->initDatabaseAdapter();
        $this->initDatabase();

        $tables = array();
        $tables['message'] = new MessageTable($this->dbAdapter, new Message());
        $tables['message_view'] = new MessageView($this->dbAdapter, new Message());
        #$tables['user'] = new UserTable($dbAdapter, new User());

        $tick = new \Core\Service\Tick(1234);
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
        $this->setExpectedException('Core\Service\Exception');
        $this->_service->getMessage(null);

    }

    public function testGetInboxMessages()
    {
        // test positive
        $object = $this->_service->getInboxMessages($this->_userA_Id);
        $this->assertEquals('Core\Model\ResultSet', get_class($object));
        $results = $object->getArrayCopy();
        $this->assertTrue(is_array($results));
        $this->assertEquals(2, count($results) );
        #$this->markTestIncomplete();

        // test negative
        $object = $this->_service->getInboxMessages(99);
        $this->assertEquals('Core\Model\ResultSet', get_class($object));
        $results = $object->getArrayCopy();
        $this->assertTrue(is_array($results));
        $this->assertEquals(0, count($results) );

        // test exception
        $this->setExpectedException('Core\Service\Exception');
        $this->_service->getInboxMessages(null);
    }

    public function testGetOutboxMessages()
    {
        // test positive
        $object = $this->_service->getOutboxMessages($this->_userA_Id);
        $this->assertEquals('Core\Model\ResultSet', get_class($object));
        $results = $object->getArrayCopy();
        $this->assertTrue(is_array($results));
        $this->assertEquals(3, count($results) );
        #$this->markTestIncomplete();

        // test negative
        $object = $this->_service->getOutboxMessages(99);
        $this->assertEquals('Core\Model\ResultSet', get_class($object));
        $results = $object->getArrayCopy();
        $this->assertTrue(is_array($results));
        $this->assertEquals(0, count($results) );

        // test exception
        $this->setExpectedException('Core\Service\Exception');
        $object = $this->_service->getOutboxMessages(null);
    }

    public function testGetArchivedMessages()
    {
        // test positive
        $object = $this->_service->getArchivedMessages($this->_userB_Id);
        $this->assertEquals('Core\Model\ResultSet', get_class($object));
        $results = $object->getArrayCopy();
        $this->assertTrue(is_array($results));
        $this->assertEquals(1, count($results) );
        #$this->markTestIncomplete();

        // test negative
        $object = $this->_service->getArchivedMessages(99);
        $this->assertEquals('Core\Model\ResultSet', get_class($object));
        $results = $object->getArrayCopy();
        $this->assertTrue(is_array($results));
        $this->assertEquals(0, count($results) );

        // test exception
        $this->setExpectedException('Core\Service\Exception');
        $this->_service->getArchivedMessages(null);
    }

    public function testSendMessage()
    {
        $msgFromUserAToUserB = array(
            'sender_id' => $this->_userA_Id,
            'recipient_id' => $this->_userB_Id,
            'attitude' => 'mood_friendly',
            'subject' => 'test',
            'text' => 'test'
        );

        $userA_outbox_before = $this->_service->getOutboxMessages($this->_userA_Id)->count();
        $userB_inbox_before  = $this->_service->getInboxMessages($this->_userB_Id)->count();
        $this->_service->sendMessage($msgFromUserAToUserB);
        $userA_outbox_after = $this->_service->getOutboxMessages($this->_userA_Id)->count();
        $userB_inbox_after  = $this->_service->getInboxMessages($this->_userB_Id)->count();

        $this->assertEquals($userA_outbox_after, $userA_outbox_before + 1);
        $this->assertEquals($userB_inbox_after, $userB_inbox_before + 1);

        $msgFromUserBToUserA = array(
            'sender_id' => $this->_userB_Id,
            'recipient_id' => $this->_userA_Id,
            'attitude' => 'mood_friendly',
            'subject' => 'test',
            'text' => 'test'
        );

        $userA_inbox_before  = $this->_service->getInboxMessages($this->_userA_Id)->count();
        $userB_outbox_before = $this->_service->getOutboxMessages($this->_userB_Id)->count();
        $this->_service->sendMessage($msgFromUserBToUserA);
        $userA_inbox_after  = $this->_service->getInboxMessages($this->_userA_Id)->count();
        $userB_outbox_after = $this->_service->getOutboxMessages($this->_userB_Id)->count();

        $this->assertEquals($userB_outbox_after, $userB_outbox_before + 1);
        $this->assertEquals($userA_inbox_after, $userA_inbox_before + 1);

    }


    public function testSetMessageStatus()
    {
        $entityId = 22;
        $entity = $this->_service->getMessage($entityId);
        $this->assertEquals(0, $entity->getIsRead());
        $this->assertEquals(0, $entity->getIsArchived());
        $this->assertEquals(0, $entity->getIsDeleted());

        $result = $this->_service->setMessageStatus($entityId, 'read');
        $this->assertEquals(1, $result);
        $result = $this->_service->setMessageStatus($entityId, 'archived');
        $this->assertEquals(1, $result);
        $result = $this->_service->setMessageStatus($entityId, 'deleted');
        $this->assertEquals(1, $result);

        $result = $this->_service->setMessageStatus($entityId, 'unknown');
        $this->assertFalse($result);

    }

}