<?php
namespace Techtree\Entity;

use Nouron\Entity\EntityInterface;

class ActionPoint implements EntityInterface
{
    private $tick;
    private $colony_id;
    private $personell_id;
    private $spend_ap;

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
     * Sets the value of tick.
     *
     * @param mixed $tick the tick
     * @return self
     */
    public function setTick($tick)
    {
        if (!is_numeric($tick) || $tick < 0) {
            throw new \Nouron\Entity\Exception('invalid tick format');
        }

        $this->tick = $tick;
        return $this;
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
     * Sets the value of colony_id.
     *
     * @param mixed $colony_id the colony_id
     * @return self
     */
    public function setColonyId($colony_id)
    {
        if (!is_numeric($colony_id) || $colony_id < 0) {
            throw new \Nouron\Entity\Exception('invalid colony id');
        }
        $this->colony_id = $colony_id;
        return $this;
    }

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
        if (!is_numeric($personell_id) || $personell_id < 0) {
            throw new \Nouron\Entity\Exception('invalid personell id');
        }
        $this->personell_id = $personell_id;
        return $this;
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

    /**
     * Sets the value of spend_ap.
     *
     * @param mixed $spend_ap the spend_ap
     * @return self
     */
    public function setSpendAp($spend_ap)
    {
        if (!is_numeric($spend_ap) || $spend_ap < 0) {
            throw new \Nouron\Entity\Exception('invalid spend ap');
        }
        $this->spend_ap = $spend_ap;
        return $this;
    }
}
