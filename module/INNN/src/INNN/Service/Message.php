<?php
namespace INNN\Service;

class Message extends \Nouron\Service\Gateway
{
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
        return $this->getTable('message')->fetchAll($where);
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
        return $this->getTable('message')->fetchAll($where);
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
        return $this->getTable('message')->fetchAll($where);
    }

    /**
     *
     * @param \INNN\Entity\Message $entity
     */
    public function sendMessage($entity)
    {
        $userTable = $this->getTable('user');
        $where = 'name = ' . $entity['recipient'];
        $user = $userTable->fetchRow($where);
        $entity['recipient_id'] = $user['id'];
        //$entity['sender_id'] = // current logged in user
        unset($newEntity['recipient']);
        unset($newEntity['submit']);
        return $this->getTable('message')->save($entity);
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