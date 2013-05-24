<?php
namespace Galaxy\Entity;

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

    public function getArrayCopy()
    {
        return get_object_vars($this);
    }
}

