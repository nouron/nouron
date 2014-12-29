<?php
namespace Fleet\Entity;

use Core\Entity\EntityInterface;

class FleetResearch implements EntityInterface
{
    private $fleet_id;
    private $research_id;
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
     * Gets the value of research_id.
     *
     * @return mixed
     */
    public function getResearchId()
    {
        return $this->research_id;
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

