<?php
namespace INNN\Service;

use Nouron\Model\ResultSet;

class MessageService extends \Nouron\Service\AbstractService
{
    /**
     *
     * @param numeric $id
     * @return \INNN\Message\Entity
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
            'is_deleted' => 0,
            'is_archived' => 0
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
            'is_deleted' => 0,
            'is_archived' => 0
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
            'recipient_id' => $userId,
            'is_deleted' => 0,
            'is_archived' => 1
        );
        return $this->getTable('message_view')->fetchAll($where);
    }

    /**
     *
     * @param \INNN\Entity\Message $entity
     * @return boolean
     */
    public function sendMessage($entity)
    {
        $this->_validateId($entity['sender_id']);
        $this->_validateId($entity['recipient_id']);
        $data = array(
            'sender_id' => $entity['sender_id'], // current logged in user
            'attitude'  => $entity['attitude'],
            'recipient_id' => $entity['recipient_id'],
            'tick' => $this->getTick(),
            'type' => 0,
            'subject' => $entity['subject'],
            'text'   => $entity['text'],
            'is_read' => 0,
            'is_archived' => 0,
            'is_deleted'  => 0
        );

        return $this->getTable('message')->save($data);
    }

    /**
     *
     * @param numeric $entity_id
     * @param string  $entity_id  'read'|'archived'|'deleted'
     * @return boolean
     */
    public function setMessageStatus($entity_id, $status)
    {
        $this->_validateId($entity_id);
        $table = $this->getTable('message');
        $entity = $table->getEntity($entity_id);

        switch ($status) {
            case 'read':     $entity->setIsRead(1); break;
            case 'archived': $entity->setIsArchived(1); break;
            case 'deleted':  $entity->setIsDeleted(1); break;
            default: return false; break;
        }
        return $table->save($entity);
    }
}