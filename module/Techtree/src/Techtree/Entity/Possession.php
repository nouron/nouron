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

    public function toArray()
    {
        return array(
            'colony_id' => $this->colony_id,
            'tech_id' => $this->tech_id,
            'name' => $this->name,
            'count' => $this->count,
            'age' => $this->age,
            'slot' => $this->slot
        );
    }
}

