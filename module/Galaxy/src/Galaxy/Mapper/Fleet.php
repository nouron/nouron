<?php
namespace Galaxy\Mapper;

use Nouron\Model\EntityInterface;

class Fleet implements EntityInterface
{
    public $id;
    public $name;
    public $user_id;
    public $x;
    public $y;

    public function toArray()
    {
        return array(
            'id' => $this->id,
            'name' => $this->name,
            'user_id' => $this->user_id,
            'x' => $this->x,
            'y' => $this->y
        );
    }
}

