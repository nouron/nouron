<?php
namespace INNN\Service;

class Gateway extends \Nouron\Service\Gateway
{
    /**
     * @return ResultSet
     */
    public function getMessages()
    {
        return $this->getTable('message')->fetchAll();
    }

    /**
     * return ResultSet
     */
    public function getEvents()
    {
        return $this->getTable('event')->fetchAll();
    }

}