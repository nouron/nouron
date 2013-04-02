<?php
namespace INNN\Service;

class Message extends \Nouron\Service\Gateway
{
    /**
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
}