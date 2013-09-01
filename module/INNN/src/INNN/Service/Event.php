<?php
namespace INNN\Service;

class Event extends \Nouron\Service\AbstractService
{
    /**
     * @return ResultSet
     */
    public function getEvents($userId)
    {
        $this->_validateId($userId);
        return $this->getTable('event')->fetchAll("id = $userId");
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