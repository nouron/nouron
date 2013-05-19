<?php
namespace Techtree\Entity;

use Nouron\Model\EntityInterface;

class Requirement implements EntityInterface
{
    public $tech_id;
    public $required_tech_id;
    public $required_tech_level;

    public function toArray()
    {
        return array(
            'tech_id' => $tech_id,
            'required_tech_id' => $required_tech_id,
            'required_tech_level' => $required_tech_level
        );
    }
}

