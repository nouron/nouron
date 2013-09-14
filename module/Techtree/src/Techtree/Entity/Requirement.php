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
    public function getTechid()
    {
        return $this->tech_id;
    }

    /**
     * Gets the value of required_tech_id.
     *
     * @return mixed
     */
    public function getRequiredTechId()
    {
        return $this->required_tech_id;
    }

    /**
     * Gets the value of required_tech_level.
     *
     * @return mixed
     */
    public function getRequiredTechLevel()
    {
        return $this->required_tech_level;
    }

    /**
     * Gets the value of zindex_priority.
     *
     * @return mixed
     */
    public function getZIndexPriority()
    {
        return $this->zindex_priority;
    }

    /**
     * Sets the value of tech_id.
     *
     * @param mixed $tech_id the tech_id
     *
     * @return self
     */
    public function setTechId($tech_id)
    {
        $this->tech_id = $tech_id;

        return $this;
    }

    /**
     * Sets the value of required_tech_id.
     *
     * @param mixed $required_tech_id the required_tech_id
     *
     * @return self
     */
    public function setRequiredTechId($required_tech_id)
    {
        $this->required_tech_id = $required_tech_id;

        return $this;
    }

    /**
     * Sets the value of required_tech_level.
     *
     * @param mixed $required_tech_level the required_tech_level
     *
     * @return self
     */
    public function setRequiredTechLevel($required_tech_level)
    {
        $this->required_tech_level = $required_tech_level;

        return $this;
    }

    /**
     * Sets the value of zindex_priority.
     *
     * @param mixed $zindex_priority the zindex_priority
     *
     * @return self
     */
    public function setZIndexPriority($zindex_priority)
    {
        $this->zindex_priority = $zindex_priority;

        return $this;
    }
}

