<?php
namespace Galaxy\Entity;

use Nouron\Entity\AbstractEntity;

class Colony extends AbstractEntity
{
    public $id;
    public $name;
    public $system_object_id;
    public $spot;
    public $user_id;
    public $since_tick;
    public $is_primary;

}

