<?php
namespace User\Service;

class User extends \Nouron\Service\Gateway
{
    /**
     * @return ResultSet
     */
    public function getUserByName($username)
    {
        return $this->getTable('user')->fetchAll("username = '$username'")->current();
    }
}