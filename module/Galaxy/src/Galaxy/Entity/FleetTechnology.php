<?php
namespace Galaxy\Entity;

use Nouron\Model\EntityInterface;

class FleetTechnology implements EntityInterface
{
    public $fleet_id;
    public $tech_id;
    public $count;
    public $is_cargo;

    public function getArrayCopy()
    {
        return get_object_vars($this);
    }
}

