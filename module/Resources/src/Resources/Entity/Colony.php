<?php
namespace Resources\Entity;

use Nouron\Entity\AbstractEntity;

class Colony extends AbstractEntity
{
    public $resource_id;
    public $colony_id;
    public $amount;


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

