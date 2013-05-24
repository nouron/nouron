<?php
namespace Galaxy\Entity;

use Nouron\Model\EntityInterface;

class Fleet implements EntityInterface
{
    public $id;
    public $name;
    public $user_id;
    public $x;
    public $y;
    public $spot;

    public function getArrayCopy()
    {
        return get_object_vars($this);
    }
}

