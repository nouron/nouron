<?php
namespace Resources\Entity;

use Nouron\Model\EntityInterface;

class Resource implements EntityInterface
{
    public $id;
    public $name;
    public $abbreviation;
    public $trigger;
    public $is_tradeable;
    public $start_amount;
    public $icon;

    public function getArrayCopy()
    {
        return get_object_vars($this);
    }
}

