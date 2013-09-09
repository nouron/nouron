<?php
namespace Galaxy\Entity;

use Nouron\Entity\AbstractEntity;

class FleetResource extends AbstractEntity
{
    public $fleet_id;
    public $resource_id;
    public $amount;
    #public $is_cargo;


    /**
     * Sets the value of fleet_id.
     *
     * @param mixed $fleet_id the fleet_id
     *
     * @return self
     */
    public function setFleet_id($fleet_id)
    {
        $this->fleet_id = $fleet_id;

        return $this;
    }

    /**
     * Sets the value of resource_id.
     *
     * @param mixed $resource_id the resource_id
     *
     * @return self
     */
    public function setResource_id($resource_id)
    {
        $this->resource_id = $resource_id;

        return $this;
    }

    /**
     * Sets the value of amount.
     *
     * @param mixed $amount the amount
     *
     * @return self
     */
    public function setAmount($amount)
    {
        $this->amount = $amount;

        return $this;
    }

    /**
     * Sets the value of is_cargo.
     *
     * @param mixed $is_cargo the is_cargo
     *
     * @return self
     */
    public function setIs_cargo($is_cargo)
    {
        $this->is_cargo = $is_cargo;

        return $this;
    }
}

