<?php
namespace Resources\Entity;

use Core\Entity\EntityInterface;

class Resource implements EntityInterface
{
    private $id;
    private $name;
    private $abbreviation;
    private $trigger;
    private $is_tradeable;
    private $start_amount;
    private $icon;


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
    public function setIsTradeable($is_tradeable)
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
    public function setStartAmount($start_amount)
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

    /**
     * Gets the value of id.
     *
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Gets the value of name.
     *
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Gets the value of abbreviation.
     *
     * @return mixed
     */
    public function getAbbreviation()
    {
        return $this->abbreviation;
    }

    /**
     * Gets the value of trigger.
     *
     * @return mixed
     */
    public function getTrigger()
    {
        return $this->trigger;
    }

    /**
     * Gets the value of is_tradeable.
     *
     * @return mixed
     */
    public function getIsTradeable()
    {
        return $this->is_tradeable;
    }

    /**
     * Gets the value of start_amount.
     *
     * @return mixed
     */
    public function getStartAmount()
    {
        return $this->start_amount;
    }

    /**
     * Gets the value of icon.
     *
     * @return mixed
     */
    public function getIcon()
    {
        return $this->icon;
    }
}

