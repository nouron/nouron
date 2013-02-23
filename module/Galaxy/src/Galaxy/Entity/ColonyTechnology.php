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

    public function toArray()
    {
        return array(
            'colony_id' => $this->colony_id,
            'tech_id' => $this->tech_id,
            'display_name' => $this->display_name,
            'count' => $this->count,
            'age' => $this->age,
            'slot' => $this->slot
        );
    }
}

