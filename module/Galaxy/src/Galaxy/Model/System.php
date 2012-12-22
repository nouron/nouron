<?php
namespace Galaxy\Model;

class System implements Nouron\Model\EntityInterface
{
    public $id;
    public $x;
    public $y;
    public $name;
    public $type_id;
    public $background_image_url;
    public $sight;
    public $density;
    public $radiation;

    public function toArray()
    {
        return array(
            'id' => $id,
            'x'  => $x,
            'y'  => $y,
            'name'  => $name,
            'type_id'  => $type_id,
            'background_image_url'  => $background_image_url,
            'sight'  => $sight,
            'density'  => $density,
            'radiation'  => $radiation
        );
    }
}