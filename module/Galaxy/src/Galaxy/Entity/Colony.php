<?php
namespace Galaxy\Entity;

use Nouron\Model\EntityInterface;

class Colony implements EntityInterface
{
    public $id;
    public $name;
    public $system_object_id;
    public $spot;
    public $user_id;
    public $since_tick;
    public $is_primary;

    public function toArray()
    {
        return array(
            'id' => $this->id,
            'name' => $this->name,
            'system_object_id' => $this->system_object_id,
            'spot' => $this->spot,
            'user_id' => $this->user_id,
            'since_tick' => $this->since_tick,
            'is_primary' => $this->is_primary
        );
    }
}

