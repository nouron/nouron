<?php
namespace Galaxy\Entity;

use Nouron\Entity\AbstractEntity;

class FleetOrder extends AbstractEntity
{
    public $tick;
    public $fleet_id;
    public $order;
    public $coordinates;
    public $data;
    public $was_processed;
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
     * Sets the value of fleet_id.
     *
     * @param mixed $fleet_id the fleet_id
     *
     * @return self
     */
    public function setFleet_id($fleet_id)
    {
        $this->fleet_id = $fleet_id;

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
     * Sets the value of coordinates.
     *
     * @param mixed $coordinates the coordinates
     *
     * @return self
     */
    public function setCoordinates($coordinates)
    {
        $this->coordinates = $coordinates;

        return $this;
    }

    /**
     * Sets the value of data.
     *
     * @param mixed $data the data
     *
     * @return self
     */
    public function setData($data)
    {
        $this->data = $data;

        return $this;
    }

    /**
     * Sets the value of was_processed.
     *
     * @param mixed $was_processed the was_processed
     *
     * @return self
     */
    public function setWas_processed($was_processed)
    {
        $this->was_processed = $was_processed;

        return $this;
    }

    /**
     * Sets the value of has_notified.
     *
     * @param mixed $has_notified the has_notified
     *
     * @return self
     */
    public function setHas_notified($has_notified)
    {
        $this->has_notified = $has_notified;

        return $this;
    }
}

