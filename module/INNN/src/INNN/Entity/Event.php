<?php
namespace INNN\Entity;

use Nouron\Entity\AbstractEntity;

class Event extends AbstractEntity
{
    public $id;
    public $user_id;
    public $tick;
    public $event;
    public $area;
    public $parameters;

}

