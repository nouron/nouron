<?php
namespace INNN\Service;

class Message extends \Nouron\Service\Gateway
{
    /**
     * @return ResultSet
     */
    public function getMessages()
    {
        return $this->getTable('message')->fetchAll();
    }
}