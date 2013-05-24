<?php
namespace Resources\Entity;

use Nouron\Model\EntityInterface;

class Colony implements EntityInterface
{
    public $resource_id;
    public $colony_id;
    public $amount;

    public function getArrayCopy()
    {
        return get_object_vars($this);
    }
}

