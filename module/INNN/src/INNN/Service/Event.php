<?php
namespace INNN\Service;

class Event extends \Nouron\Service\Gateway
{
    /**
     * @return ResultSet
     */
    public function getEvents()
    {
        return $this->getTable('events')->fetchAll();
    }
}