<?php
namespace Galaxy\Entity;

use Nouron\Model\EntityInterface;

class SystemObject implements EntityInterface
{
    public $id;
    public $name;
    public $x;
    public $y;
    public $type_id;
    public $sight;
    public $density;
    public $radiation;
    public $type;
    public $image_url;

    public function toArray()
    {
        return array(
            'id' => $this->id,
            'name' => $this->name,
            'x' => $this->x,
            'y' => $this->y,
            'type_id' => $this->type_id,
            'sight' => $this->sight,
            'density' => $this->density,
            'radiation' => $this->radiation,
            'type' => $this->type,
            'image_url' => $this->image_url
        );
    }
}

