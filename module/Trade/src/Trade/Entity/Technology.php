<?php
namespace Trade\Entity;

use Nouron\Model\EntityInterface;

class Technology implements EntityInterface
{
    public $colony_id;
    public $direction;
    public $tech_id;
    public $amount;
    public $price;
    public $restriction;

    public function getArrayCopy()
    {
        return get_object_vars($this);
    }
}

