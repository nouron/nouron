<?php
namespace Galaxy\Entity;

use Nouron\Entity\AbstractEntity;

class ColonyTechnology extends AbstractEntity
{
    public $colony_id;
    public $tech_id;
    public $display_name;
    public $count;
    public $age;
    public $slot;

}

