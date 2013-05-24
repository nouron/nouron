<?php
namespace Galaxy\Entity;

use Nouron\Model\EntityInterface;

class Colony implements EntityInterface
{
    public $id;
    public $name;
    public $system_object_id;
    public $spot;
    public $user_id;
    public $since_tick;
    public $is_primary;

    public function getArrayCopy()
    {
        return get_object_vars($this);
    }
}

