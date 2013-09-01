<?php
namespace Trade\Entity;

use Nouron\Entity\AbstractEntity;

class Technology extends AbstractEntity
{
    public $colony_id;
    public $direction;
    public $tech_id;
    public $amount;
    public $price;
    public $restriction;

}

