<?php
namespace INNN\Service;

class Event extends \Nouron\Service\Gateway
{
    /**
     * @return ResultSet
     */
    public function getEvents($userId)
    {
        $this->_validateId($userId);
        return $this->getTable('event')->fetchAll("id = $userId");
    }
}