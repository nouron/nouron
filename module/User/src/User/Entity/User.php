<?php
namespace User\Entity;

use Nouron\Model\EntityInterface;

class User extends \ZfcUser\Entity\User implements EntityInterface
{
    protected $tableName  = 'usr_users';

    public function toArray()
    {
        return array(
            'id' => $this->id,
            'username' => $this->username,
            'email' => $this->email,
            'displayName' => $this->displayName,
            'password' => $this->password,
            'state' => $this->state
        );
    }
}

