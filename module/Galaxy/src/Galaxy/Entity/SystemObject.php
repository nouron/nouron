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

    public function getArrayCopy()
    {
        return get_object_vars($this);
    }
}

