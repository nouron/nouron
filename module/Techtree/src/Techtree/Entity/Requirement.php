<?php
namespace Techtree\Entity;

use Nouron\Entity\AbstractEntity;

class Requirement extends AbstractEntity
{
    public $tech_id;
    public $required_tech_id;
    public $required_tech_level;
    public $zindex_priority;
}

