<?php
namespace Trade\Entity;

use Nouron\Entity\AbstractEntity;

class Resource extends AbstractEntity
{
    public $colony_id;
    public $direction;
    public $resource_id;
    public $amount;
    public $price;
    public $restriction;

}

