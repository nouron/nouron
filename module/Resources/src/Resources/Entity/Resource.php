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


    /**
     * Sets the value of id.
     *
     * @param mixed $id the id
     *
     * @return self
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Sets the value of name.
     *
     * @param mixed $name the name
     *
     * @return self
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Sets the value of abbreviation.
     *
     * @param mixed $abbreviation the abbreviation
     *
     * @return self
     */
    public function setAbbreviation($abbreviation)
    {
        $this->abbreviation = $abbreviation;

        return $this;
    }

    /**
     * Sets the value of trigger.
     *
     * @param mixed $trigger the trigger
     *
     * @return self
     */
    public function setTrigger($trigger)
    {
        $this->trigger = $trigger;

        return $this;
    }

    /**
     * Sets the value of is_tradeable.
     *
     * @param mixed $is_tradeable the is_tradeable
     *
     * @return self
     */
    public function setIs_tradeable($is_tradeable)
    {
        $this->is_tradeable = $is_tradeable;

        return $this;
    }

    /**
     * Sets the value of start_amount.
     *
     * @param mixed $start_amount the start_amount
     *
     * @return self
     */
    public function setStart_amount($start_amount)
    {
        $this->start_amount = $start_amount;

        return $this;
    }

    /**
     * Sets the value of icon.
     *
     * @param mixed $icon the icon
     *
     * @return self
     */
    public function setIcon($icon)
    {
        $this->icon = $icon;

        return $this;
    }
}

