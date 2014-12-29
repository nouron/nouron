<?php
namespace Fleet\Entity;

use Core\Entity\EntityInterface;

class FleetResource implements EntityInterface
{
    private $fleet_id;
    private $resource_id;
    private $amount;
    #public $is_cargo;

    /**
     * Sets the value of fleet_id.
     *
     * @param mixed $fleet_id the fleet_id
     *
     * @return self
     */
    public function setFleetId($fleet_id)
    {
        if (!is_numeric($fleet_id) || $fleet_id < 0) {
            throw new \Core\Entity\Exception('invalid fleet id');
        }
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
    public function setResourceId($resource_id)
    {
        if (!is_numeric($resource_id) || $resource_id < 0) {
            throw new \Core\Entity\Exception('invalid resource id');
        }
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
        if (!is_numeric($amount) || $amount < 0) {
            throw new \Core\Entity\Exception('invalid amount');
        }
        $this->amount = $amount;
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
     * Gets the value of resource_id.
     *
     * @return mixed
     */
    public function getResourceId()
    {
        return $this->resource_id;
    }

    /**
     * Gets the value of amount.
     *
     * @return mixed
     */
    public function getAmount()
    {
        return $this->amount;
    }

}

