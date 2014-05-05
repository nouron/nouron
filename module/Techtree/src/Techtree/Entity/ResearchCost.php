<?php
namespace Techtree\Entity;

use Nouron\Entity\AbstractEntity;

class ResearchCost extends AbstractEntity
{
    private $research_id;
    private $resource_id;
    private $amount;

    /**
     * Gets the value of research_id.
     *
     * @return mixed
     */
    public function getResearchId()
    {
        return $this->research_id;
    }

    /**
     * Sets the value of research_id.
     *
     * @param mixed $research_id the research_id
     *
     * @return self
     */
    public function setResearchId($research_id)
    {
        $this->research_id = $research_id;

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

