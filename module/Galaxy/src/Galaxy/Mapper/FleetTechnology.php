<?php
namespace Galaxy\Mapper;

use Nouron\Model\EntityInterface;

class FleetTechnology implements EntityInterface
{
    public $fleet_id;
    public $tech_id;
    public $count;
    public $is_cargo;

    public function toArray()
    {
        return array(
            'fleet_id' => $this->fleet_id,
            'tech_id' => $this->tech_id,
            'count' => $this->count,
            'is_cargo' => $this->is_cargo
        );
    }
}

