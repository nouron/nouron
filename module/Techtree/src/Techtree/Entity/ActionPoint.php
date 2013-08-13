<?php
namespace Techtree\Entity;

use Nouron\Model\EntityInterface;

class ActionPoint implements EntityInterface
{
    public $tick;
    public $colony_id;
    public $personell_tech_id;
    public $spend_ap;

    public function getArrayCopy()
    {
        return get_object_vars($this);
    }
}

