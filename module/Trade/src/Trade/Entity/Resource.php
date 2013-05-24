<?php
namespace Trade\Entity;

use Nouron\Model\EntityInterface;

class Resource implements EntityInterface
{
    public $colony_id;
    public $direction;
    public $resource_id;
    public $amount;
    public $price;
    public $restriction;

    public function getArrayCopy()
    {
        return get_object_vars($this);
    }
}

