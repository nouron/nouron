<?php
namespace INNN\Service;

class EventService extends \Core\Service\AbstractService
{
    /**
     *
     * @param numeric $id
     * @return \INNN\Message\Entity
     */
    public function getEvent($id)
    {
        $this->_validateId($id);
        return $this->getTable('event')->getEntity($id);
    }

    /**
     * @return ResultSet
     */
    public function getEvents($userId)
    {
        $this->_validateId($userId);
        return $this->getTable('event')->fetchAll("user = $userId");
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