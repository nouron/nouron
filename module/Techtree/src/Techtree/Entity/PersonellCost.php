<?php
namespace Techtree\Entity;

use Core\Entity\EntityInterface;

class PersonellCost implements EntityInterface
{
    private $personell_id;
    private $resource_id;
    private $amount;

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

