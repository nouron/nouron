<?php
namespace Techtree\Entity;

use Nouron\Entity\AbstractEntity;
use Zend\Stdlib\Hydrator\ObjectProperty;

class Technology extends AbstractEntity
{
    public $id;
    public $type;
    public $purpose;
    public $name;
    public $prime_colony_only;
    public $decay;
    public $tradeable;
    public $moving_speed;
    public $row;
    public $column;
    public $max_level;
    public $max_status_points;
    public $ap_for_levelup;


}

