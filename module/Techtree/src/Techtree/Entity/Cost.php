<?php
namespace Techtree\Entity;

use Nouron\Model\EntityInterface;

class Cost implements EntityInterface
{
    public $tech_id;
    public $resource_id;
    public $amount;

    public function getArrayCopy()
    {
        return get_object_vars($this);
    }
}

