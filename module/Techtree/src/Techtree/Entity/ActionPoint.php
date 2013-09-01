<?php
namespace Techtree\Entity;

use Nouron\Entity\AbstractEntity;
use Zend\Stdlib\Hydrator\ArraySerializable;

class ActionPoint extends AbstractEntity
{
    public $tick;
    public $colony_id;
    public $personell_tech_id;
    public $spend_ap;
}

