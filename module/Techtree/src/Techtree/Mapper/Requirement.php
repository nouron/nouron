<?php
namespace Techtree\Mapper;

use Nouron\Model\EntityInterface;

class Requirement implements EntityInterface
{
    public $tech_id;
    public $required_tech_id;
    public $count;

    public function toArray()
    {
        return array(
        );
    }
}

