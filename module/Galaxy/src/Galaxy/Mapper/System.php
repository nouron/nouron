<?php
namespace Galaxy\Mapper;

use Nouron\Model\EntityInterface;

class System implements EntityInterface
{
    public $id;
    public $name;
    public $x;
    public $y;
    public $type_id;
    public $background_image_url;
    public $sight;
    public $density;
    public $radiation;

    public function toArray()
    {
        return array(
            'id' => $this->id,
            'name' => $this->name,
            'x' => $this->x,
            'y' => $this->y,
            'type_id' => $this->type_id,
            'background_image_url' => $this->background_image_url,
            'sight' => $this->sight,
            'density' => $this->density,
            'radiation' => $this->radiation
        );
    }
}

