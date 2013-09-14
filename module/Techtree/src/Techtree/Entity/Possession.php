<?php
namespace Techtree\Entity;

use Nouron\Entity\AbstractEntity;

class Possession extends AbstractEntity
{
    public $colony_id;
    public $tech_id;
    public $display_name;
    public $level;
    public $status_points;
    public $ap_spend;
    //public $ap_spend_for_remove;
    public $slot;

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
     * Sets the value of display_name.
     *
     * @param mixed $display_name the display_name
     *
     * @return self
     */
    public function setDisplayName($display_name)
    {
        $this->display_name = $display_name;

        return $this;
    }

    /**
     * Sets the value of level.
     *
     * @param mixed $level the level
     *
     * @return self
     */
    public function setLevel($level)
    {
        $this->level = $level;

        return $this;
    }

    /**
     * Sets the value of status_points.
     *
     * @param mixed $status_points the status_points
     *
     * @return self
     */
    public function setStatusPoints($status_points)
    {
        $this->status_points = $status_points;

        return $this;
    }

    /**
     * Sets the value of ap_spend.
     *
     * @param mixed $ap_spend the ap_spend
     *
     * @return self
     */
    public function setApSpend($ap_spend)
    {
        $this->ap_spend = $ap_spend;

        return $this;
    }

    /**
     * Sets the value of ap_spend_for_remove.
     *
     * @param mixed $ap_spend_for_remove the ap_spend_for_remove
     *
     * @return self
     */
    public function setApSpendForRemove($ap_spend_for_remove)
    {
        $this->ap_spend_for_remove = $ap_spend_for_remove;

        return $this;
    }

    /**
     * Sets the value of slot.
     *
     * @param mixed $slot the slot
     *
     * @return self
     */
    public function setSlot($slot)
    {
        $this->slot = $slot;

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
     * Gets the value of tech_id.
     *
     * @return mixed
     */
    public function getTechId()
    {
        return $this->tech_id;
    }

    /**
     * Gets the value of display_name.
     *
     * @return mixed
     */
    public function getDisplayName()
    {
        return $this->display_name;
    }

    /**
     * Gets the value of level.
     *
     * @return mixed
     */
    public function getLevel()
    {
        return $this->level;
    }

    /**
     * Gets the value of status_points.
     *
     * @return mixed
     */
    public function getStatusPoints()
    {
        return $this->status_points;
    }

    /**
     * Gets the value of ap_spend.
     *
     * @return mixed
     */
    public function getApSpend()
    {
        return $this->ap_spend;
    }

    /**
     * Gets the value of ap_spend_for_remove.
     *
     * @return mixed
     */
    public function getApSpendForRemove()
    {
        return $this->ap_spend_for_remove;
    }

    /**
     * Gets the value of slot.
     *
     * @return mixed
     */
    public function getSlot()
    {
        return $this->slot;
    }
}

