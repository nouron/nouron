<?php
namespace Techtree\Entity;

use Nouron\Entity\AbstractEntity;

class Order extends AbstractEntity
{
    public $tick;
    public $colony_id;
    public $tech_id;
    public $order;
    public $ap_ordered;
    public $is_final_step;
    public $was_progressed;
    public $has_notified;


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
     * Sets the value of order.
     *
     * @param mixed $order the order
     *
     * @return self
     */
    public function setOrder($order)
    {
        $this->order = $order;

        return $this;
    }

    /**
     * Sets the value of ap_ordered.
     *
     * @param mixed $ap_ordered the ap_ordered
     *
     * @return self
     */
    public function setApOrdered($ap_ordered)
    {
        $this->ap_ordered = $ap_ordered;

        return $this;
    }

    /**
     * Sets the value of is_final_step.
     *
     * @param mixed $is_final_step the is_final_step
     *
     * @return self
     */
    public function setIsFinalStep($is_final_step)
    {
        $this->is_final_step = $is_final_step;

        return $this;
    }

    /**
     * Sets the value of was_progressed.
     *
     * @param mixed $was_progressed the was_progressed
     *
     * @return self
     */
    public function setWasProgressed($was_progressed)
    {
        $this->was_progressed = $was_progressed;

        return $this;
    }

    /**
     * Sets the value of has_notified.
     *
     * @param mixed $has_notified the has_notified
     *
     * @return self
     */
    public function setHasNotified($has_notified)
    {
        $this->has_notified = $has_notified;

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
     * Gets the value of tech_id.
     *
     * @return mixed
     */
    public function getTechId()
    {
        return $this->tech_id;
    }

    /**
     * Gets the value of order.
     *
     * @return mixed
     */
    public function getOrder()
    {
        return $this->order;
    }

    /**
     * Gets the value of ap_ordered.
     *
     * @return mixed
     */
    public function getApOrdered()
    {
        return $this->ap_ordered;
    }

    /**
     * Gets the value of is_final_step.
     *
     * @return mixed
     */
    public function getIsFinalStep()
    {
        return $this->is_final_step;
    }

    /**
     * Gets the value of was_progressed.
     *
     * @return mixed
     */
    public function getWasProgressed()
    {
        return $this->was_progressed;
    }

    /**
     * Gets the value of has_notified.
     *
     * @return mixed
     */
    public function getHasNotified()
    {
        return $this->has_notified;
    }
}

