<?php
namespace Techtree\Entity;

use Nouron\Entity\EntityInterface;

class BuildingCost implements EntityInterface
{
    private $building_id;
    private $resource_id;
    private $amount;


    /**
     * Gets the value of building_id.
     *
     * @return mixed
     */
    public function getBuildingId()
    {
        return $this->building_id;
    }

    /**
     * Sets the value of building_id.
     *
     * @param mixed $building_id the building_id
     *
     * @return self
     */
    public function setBuildingId($building_id)
    {
        $this->building_id = $building_id;

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

