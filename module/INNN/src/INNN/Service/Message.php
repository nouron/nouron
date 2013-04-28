<?php
namespace INNN\Service;

class Message extends \Nouron\Service\Gateway
{
    /**
     *
     * @param numeric $id
     */
    public function getMessage($id)
    {
        $this->_validateId($id);
        return $this->getTable('message_view')->getEntity($id);
    }


    /**
     * @param  numeric $userId
     * @return ResultSet
     */
    public function getInboxMessages($userId)
    {
        $this->_validateId($userId);
        $where = array(
            'recipient_id' => $userId,
            'isDeleted' => 0,
            'isArchived' => 0
        );
        return $this->getTable('message_view')->fetchAll($where, "tick DESC");
    }

    /**
     * @param  numeric $userId
     * @return ResultSet
     */
    public function getOutboxMessages($userId)
    {
        $this->_validateId($userId);
        $where = array(
            'sender_id' => $userId,
            'isDeleted' => 0,
            'isArchived' => 0
        );
        return $this->getTable('message_view')->fetchAll($where);
    }

    /**
     * @param  numeric $userId
     * @return ResultSet
     */
    public function getArchivedMessages($userId)
    {
        $this->_validateId($userId);
        $where = array(
            'sender_id' => $userId,
            'isDeleted' => 0,
            'isArchived' => 1
        );
        return $this->getTable('message_view')->fetchAll($where);
    }

    /**
     *
     * @param \INNN\Entity\Message $entity
     */
    public function sendMessage($entity)
    {
        print_r($entity);
        $this->_validateId($entity['sender_id']);
        $this->_validateId($entity['recipient_id']);
        $data = array(
            'sender_id' => $entity['sender_id'], // current logged in user
            'attitude'  => $entity['mood'],
            'recipient_id' => $entity['recipient_id'],
            'tick' => $this->getTick(),
            'type' => 0,
            'subject' => $entity['subject'],
            'text'   => $entity['text'],
            'isRead' => 0,
            'isArchived' => 0,
            'isDeleted'  => 0
        );

        return $this->getTable('message')->save($data);
    }

    /**
     *
     * @param numeric $entity_id
     * @param string  $entity_id  'read'|'archived'|'deleted'
     */
    public function setMessageStatus($entity_id, $status)
    {
        $this->_validateId($entity_id);
        $table = $this->getTable('message');
        $entity = $table->getEntity($entity_id);
        switch ($status) {
            case 'read':     $entity->isRead = 1; break;
            case 'archived': $enitity->isArchived = 1; break;
            case 'deleted':  $entity->isDeleted = 1; break;
            default: return false; break;
        }
        return $table->save($entity);
    }

    /**
     *
     * @param \INNN\Entity\Event $entity
     */
    public function createEvent($entity)
    {
        return $this->getTable('event')->save($entity);
    }
}