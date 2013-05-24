<?php
namespace Techtree\Entity;

use Nouron\Model\EntityInterface;

class Requirement implements EntityInterface
{
    public $tech_id;
    public $required_tech_id;
    public $required_tech_level;

    public function getArrayCopy()
    {
        return get_object_vars($this);
    }
}

