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
    public function setColony_id($colony_id)
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
    public function setTech_id($tech_id)
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
    public function setDisplay_name($display_name)
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
}

