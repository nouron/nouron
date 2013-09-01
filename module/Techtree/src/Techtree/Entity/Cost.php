<?php
namespace Techtree\Entity;

use Nouron\Entity\AbstractEntity;

class Cost extends AbstractEntity
{
    public $tech_id;
    public $resource_id;
    public $amount;

    public function getArrayCopy()
    {
        return get_object_vars($this);
    }
}

