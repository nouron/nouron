<?php
namespace Techtree\Entity;

use Nouron\Model\EntityInterface;

class Technology implements EntityInterface
{
    public $id;
    public $type;
    public $purpose;
    public $name;
    public $prime_colony_only;
    public $decay;
    public $tradeable;
    public $moving_speed;

    public function getArrayCopy()
    {
        return get_object_vars($this);
    }
}

