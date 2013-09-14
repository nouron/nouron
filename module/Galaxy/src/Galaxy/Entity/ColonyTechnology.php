<?php
namespace Galaxy\Entity;

use Nouron\Entity\AbstractEntity;

class ColonyTechnology extends AbstractEntity
{
    public $colony_id;
    public $tech_id;
    public $display_name;
    public $count;
    public $age;
    public $slot;


    /**
     * Sets the value of colony_id.
     *
     * @param mixed $colony_id the colony_id
     *
     * @return self
     */
    public function setColonyId($colony_id)
    {
        $this->colony_id = $colony_id;

        return $this;
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
     * Sets the value of display_name.
     *
     * @param mixed $display_name the display_name
     *
     * @return self
     */
    public function setDisplayName($display_name)
    {
        $this->display_name = $display_name;

        return $this;
    }

    /**
     * Sets the value of count.
     *
     * @param mixed $count the count
     *
     * @return self
     */
    public function setCount($count)
    {
        $this->count = $count;

        return $this;
    }

    /**
     * Sets the value of age.
     *
     * @param mixed $age the age
     *
     * @return self
     */
    public function setAge($age)
    {
        $this->age = $age;

        return $this;
    }

    /**
     * Sets the value of slot.
     *
     * @param mixed $slot the slot
     *
     * @return self
     */
    public function setSlot($slot)
    {
        $this->slot = $slot;

        return $this;
    }

    /**
     * Gets the value of colony_id.
     *
     * @return mixed
     */
    public function getColonyId()
    {
        return $this->colony_id;
    }

    /**
     * Gets the value of tech_id.
     *
     * @return mixed
     */
    public function getTechId()
    {
        return $this->tech_id;
    }

    /**
     * Gets the value of display_name.
     *
     * @return mixed
     */
    public function getDisplayName()
    {
        return $this->display_name;
    }

    /**
     * Gets the value of count.
     *
     * @return mixed
     */
    public function getCount()
    {
        return $this->count;
    }

    /**
     * Gets the value of age.
     *
     * @return mixed
     */
    public function getAge()
    {
        return $this->age;
    }

    /**
     * Gets the value of slot.
     *
     * @return mixed
     */
    public function getSlot()
    {
        return $this->slot;
    }
}

