<?php
namespace Techtree\Entity;

use Nouron\Entity\AbstractEntity;

class Cost extends AbstractEntity
{
    public $tech_id;
    public $resource_id;
    public $amount;

    public function getArrayCopy()
    {
        return get_object_vars($this);
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
     * Gets the value of tech_id.
     *
     * @return mixed
     */
    public function getTechId()
    {
        return $this->tech_id;
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

