<?php
namespace Fleet\Entity;

use Core\Entity\EntityInterface;

class FleetPersonell implements EntityInterface
{
    private $fleet_id;
    private $personell_id;
    private $count;
    private $is_cargo;

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
     * Sets the value of personell_id.
     *
     * @param mixed $personell_id the personell_id
     *
     * @return self
     */
    public function setPersonellId($personell_id)
    {
        $this->personell_id = $personell_id;

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
     * Gets the value of personell_id.
     *
     * @return mixed
     */
    public function getPersonellId()
    {
        return $this->personell_id;
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

