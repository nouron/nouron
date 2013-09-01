<?php
namespace Resources\Entity;

use Nouron\Entity\AbstractEntity;

class Resource extends AbstractEntity
{
    public $id;
    public $name;
    public $abbreviation;
    public $trigger;
    public $is_tradeable;
    public $start_amount;
    public $icon;

}

