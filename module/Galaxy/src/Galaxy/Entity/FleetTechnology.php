<?php
namespace Galaxy\Entity;

use Nouron\Entity\AbstractEntity;

class FleetTechnology extends AbstractEntity
{
    public $fleet_id;
    public $tech_id;
    public $count;
    public $is_cargo;

    /**
     * Sets the value of fleet_id.
     *
     * @param mixed $fleet_id the fleet_id
     *
     * @return self
     */
    public function setFleetId($fleet_id)
    {
        $this->fleet_id = $fleet_id;

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
     * Sets the value of is_cargo.
     *
     * @param mixed $is_cargo the is_cargo
     *
     * @return self
     */
    public function setIsCargo($is_cargo)
    {
        $this->is_cargo = $is_cargo;

        return $this;
    }

    /**
     * Gets the value of fleet_id.
     *
     * @return mixed
     */
    public function getFleetId()
    {
        return $this->fleet_id;
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
     * Gets the value of count.
     *
     * @return mixed
     */
    public function getCount()
    {
        return $this->count;
    }

    /**
     * Gets the value of is_cargo.
     *
     * @return mixed
     */
    public function getIsCargo()
    {
        return $this->is_cargo;
    }
}

