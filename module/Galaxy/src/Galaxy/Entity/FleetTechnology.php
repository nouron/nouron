<?php
namespace Galaxy\Entity;

use Nouron\Entity\AbstractEntity;

class FleetTechnology extends AbstractEntity
{
    public $fleet_id;
    public $tech_id;
    public $count;
    public $is_cargo;
}

