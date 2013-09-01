<?php
namespace Resources\Entity;

use Nouron\Entity\AbstractEntity;
use Zend\Stdlib\Hydrator\ObjectProperty;

class User extends AbstractEntity
{
    public $user_id;
    public $credits;
    public $supply;

}

