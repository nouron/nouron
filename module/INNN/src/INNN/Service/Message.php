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
        return $this->getTable('message')->fetchAll($where, "tick DESC");
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
        $where = 'username = "' . $entity['recipient'] . '"';
        $user = $userTable->fetchRow($where);

        $data = array(
            'sender_id' => $_SESSION['userId'], // current logged in user
            'attitude'  => $entity['mood'],
            'recipient_id' => $user['user_id'],
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
     * @param \INNN\Entity\Event $entity
     */
    public function createEvent($entity)
    {
        return $this->getTable('event')->save($entity);
    }
}