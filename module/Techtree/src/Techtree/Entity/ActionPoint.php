<?php
namespace Techtree\Entity;

use Nouron\Entity\AbstractEntity;
use Zend\Stdlib\Hydrator\ArraySerializable;

class ActionPoint extends AbstractEntity
{
    public $tick;
    public $colony_id;
    public $personell_tech_id;
    public $spend_ap;

    /**
     * Sets the value of tick.
     *
     * @param mixed $tick the tick
     *
     * @return self
     */
    public function setTick($tick)
    {
        $this->tick = $tick;

        return $this;
    }

    /**
     * Sets the value of colony_id.
     *
     * @param mixed $colony_id the colony_id
     *
     * @return self
     */
    public function setColonyId($colony_id)
    {
        $this->colony_id = $colony_id;

        return $this;
    }

    /**
     * Sets the value of personell_tech_id.
     *
     * @param mixed $personell_tech_id the personell_tech_id
     *
     * @return self
     */
    public function setPersonellTechId($personell_tech_id)
    {
        $this->personell_tech_id = $personell_tech_id;

        return $this;
    }

    /**
     * Sets the value of spend_ap.
     *
     * @param mixed $spend_ap the spend_ap
     *
     * @return self
     */
    public function setSpendAp($spend_ap)
    {
        $this->spend_ap = $spend_ap;

        return $this;
    }

    /**
     * Gets the value of tick.
     *
     * @return mixed
     */
    public function getTick()
    {
        return $this->tick;
    }

    /**
     * Gets the value of colony_id.
     *
     * @return mixed
     */
    public function getColonyId()
    {
        return $this->colony_id;
    }

    /**
     * Gets the value of personell_tech_id.
     *
     * @return mixed
     */
    public function getPersonellTechId()
    {
        return $this->personell_tech_id;
    }

    /**
     * Gets the value of spend_ap.
     *
     * @return mixed
     */
    public function getSpendAp()
    {
        return $this->spend_ap;
    }
}

