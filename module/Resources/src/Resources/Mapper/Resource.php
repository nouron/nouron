<?php
namespace Resources\Mapper;

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

    public function toArray()
    {
        return array(
            'id' => $this->id,
            'name' => $this->name,
            'abbreviation' => $this->abbreviation,
            'trigger' => $this->trigger,
            'is_tradeable' => $this->is_tradeable,
            'start_amount' => $this->start_amount,
            'icon' => $this->icon
        );
    }
}

