<?php
namespace Resources\Entity;

use Nouron\Model\EntityInterface;

class User implements EntityInterface
{
    public $user_id;
    public $credits;
    public $supply;

    public function getArrayCopy()
    {
        return get_object_vars($this);
    }
}

