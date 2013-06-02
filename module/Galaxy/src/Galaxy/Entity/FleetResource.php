<?php
namespace Galaxy\Entity;

use Nouron\Model\EntityInterface;

class FleetResource implements EntityInterface
{
    public $fleet_id;
    public $resource_id;
    public $amount;
    #public $is_cargo;

    public function getArrayCopy()
    {
        return get_object_vars($this);
    }
}

