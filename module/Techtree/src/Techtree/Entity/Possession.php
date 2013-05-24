<?php
namespace Techtree\Entity;

use Nouron\Model\EntityInterface;

class Possession implements EntityInterface
{
    public $colony_id;
    public $tech_id;
    public $name;
    public $count;
    public $age;
    public $slot;

    public function getArrayCopy()
    {
        return get_object_vars($this);
    }
}

