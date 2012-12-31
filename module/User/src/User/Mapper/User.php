<?php
namespace User\Mapper;

use Nouron\Model\EntityInterface;

class User extends \ZfcUser\Entity\User implements EntityInterface
{
    protected $tableName  = 'usr_users';

    public function toArray()
    {
        return array(
            'id' => $this->id,
        );
    }
}

