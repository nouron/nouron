<?php
namespace Techtree\Entity;

use Nouron\Entity\EntityInterface;

class ShipCost implements EntityInterface
{
    private $ship_id;
    private $resource_id;
    private $amount;


    /**
     * Gets the value of ship_id.
     *
     * @return mixed
     */
    public function getShipId()
    {
        return $this->ship_id;
    }

    /**
     * Sets the value of ship_id.
     *
     * @param mixed $ship_id the ship_id
     *
     * @return self
     */
    public function setShipId($ship_id)
    {
        $this->ship_id = $ship_id;

        return $this;
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
     * Sets the value of resource_id.
     *
     * @param mixed $resource_id the resource_id
     *
     * @return self
     */
    public function setResourceId($resource_id)
    {
        $this->resource_id = $resource_id;

        return $this;
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
}

