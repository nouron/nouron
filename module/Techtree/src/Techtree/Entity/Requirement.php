<?php
namespace Techtree\Entity;

use Nouron\Entity\AbstractEntity;

class Requirement extends AbstractEntity
{
    public $tech_id;
    public $required_tech_id;
    public $required_tech_level;
    public $zindex_priority;

    /**
     * Gets the value of tech_id.
     *
     * @return mixed
     */
    public function getTech_id()
    {
        return $this->tech_id;
    }

    /**
     * Gets the value of required_tech_id.
     *
     * @return mixed
     */
    public function getRequired_tech_id()
    {
        return $this->required_tech_id;
    }

    /**
     * Gets the value of required_tech_level.
     *
     * @return mixed
     */
    public function getRequired_tech_level()
    {
        return $this->required_tech_level;
    }

    /**
     * Gets the value of zindex_priority.
     *
     * @return mixed
     */
    public function getZindex_priority()
    {
        return $this->zindex_priority;
    }
}

