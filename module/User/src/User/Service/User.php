<?php
namespace User\Service;

class User extends \Nouron\Service\AbstractService
{
    /**
     * @return ResultSet
     */
    public function getUserByName($username)
    {
        return $this->getTable('user')->fetchAll("username = '$username'")->current();
    }
}