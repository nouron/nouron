<?php
namespace Galaxy\Entity;

use Nouron\Model\EntityInterface;

class ColonyTechnology implements EntityInterface
{
    public $colony_id;
    public $tech_id;
    public $display_name;
    public $count;
    public $age;
    public $slot;

    public function getArrayCopy()
    {
        return get_object_vars($this);
    }
}

