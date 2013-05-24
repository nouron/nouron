<?php
namespace User\Entity;

use Nouron\Model\EntityInterface;
use ZfcRbac\Identity\IdentityInterface;

class User extends \ZfcUser\Entity\User implements EntityInterface, IdentityInterface
{
    protected $tableName  = 'user';

    public function getArrayCopy()
    {
        return array(
            'id' => $this->id,
            'username' => $this->username,
            'email' => $this->email,
            'displayName' => $this->displayName,
            #'password' => $this->password,
            'state' => $this->state,
            #'role' => $this->role
        );
    }

    public function getRoles()
    {
        return array($this->role);
    }
}

