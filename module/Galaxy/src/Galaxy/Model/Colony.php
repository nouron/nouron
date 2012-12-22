<?php
namespace Galaxy\Model;

class Colony implements Nouron\Model\EntityInterface
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
            'id' => $id,
            'name' => $name,
            'system_object_id' => $system_object_id,
            'spot' => $spot,
            'user_id' => $user_id,
            'since_tick' => $since_tick,
            'is_primary' => $is_primary
        );
    }
}