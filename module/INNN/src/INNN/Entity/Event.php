<?php
namespace INNN\Entity;

use Nouron\Model\EntityInterface;

class Event implements EntityInterface
{
    public $id;
    public $user_id;
    public $tick;
    public $event;
    public $area;
    public $parameters;

    public function getArrayCopy()
    {
        return get_object_vars($this);
    }
}

