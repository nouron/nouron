<?php
namespace Fleet\Entity;

use Nouron\Entity\EntityInterface;

class FleetOrder implements EntityInterface
{
    private $tick;
    private $fleet_id;
    private $order;
    private $coordinates;
    private $data;
    private $was_processed;
    private $has_notified;

    /**
     * Sets the value of tick.
     *
     * @param mixed $tick the tick
     * @return self
     */
    public function setTick($tick)
    {
        if (!is_numeric($tick) || $tick < 0) {
            throw new \Nouron\Entity\Exception('invalid tick');
        }
        $this->tick = (int) $tick;
        return $this;
    }

    /**
     * Sets the value of fleet_id.
     *
     * @param mixed $fleet_id the fleet_id
     * @return self
     */
    public function setFleetId($fleet_id)
    {
        if (!is_numeric($fleet_id) || $fleet_id < 0) {
            throw new \Nouron\Entity\Exception('invalid fleet id');
        }
        $this->fleet_id = (int) $fleet_id;
        return $this;
    }

    /**
     * Sets the value of order.
     *
     * @param mixed $order the order
     * @return self
     */
    public function setOrder($order)
    {
        if (!in_array($order, array('move', 'attack', 'join', 'trade', 'hold', 'convoy', 'defend')))
        {
            throw new \Nouron\Entity\Exception('invalid order command');
        }
        $this->order = (string) $order;
        return $this;
    }

    /**
     * Sets the value of coordinates.
     *
     * @param  json|array $coordinates the coordinates as json string or array
     * @return self
     */
    public function setCoordinates($coordinates)
    {
        if (is_array($coordinates)) {
            $coordinates = json_encode($coordinates);
        }
        $coords = json_decode($coordinates);
        if (empty($coords)) {
            throw new \Nouron\Entity\Exception('invalid coordinates format');
        }
        $this->coordinates = $coords;
        return $this;
    }

    /**
     * Sets the value of data.
     *
     * @param  json|array $data the data
     * @return self
     */
    public function setData($data)
    {
        if (is_array($data)) {
            $data = json_encode($data);
        }
        $data = json_decode($data);
        if (empty($data)) {
            throw new \Nouron\Entity\Exception('invalid data format');
        }
        $this->data = $data;
        return $this;
    }

    /**
     * Sets the value of was_processed.
     *
     * @param mixed $was_processed the was_processed
     * @return self
     */
    public function setWasProcessed($was_processed)
    {
        $this->was_processed = (bool) $was_processed;
        return $this;
    }

    /**
     * Sets the value of has_notified.
     *
     * @param  boolean $has_notified the has_notified
     * @return self
     */
    public function setHasNotified($has_notified)
    {
        $this->has_notified = (bool) $has_notified;
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
     * Gets the value of fleet_id.
     *
     * @return mixed
     */
    public function getFleetId()
    {
        return $this->fleet_id;
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
     * Gets the value of coordinates.
     *
     * @return array
     */
    public function getCoordinates()
    {
        return $this->coordinates;
    }

    /**
     * Gets the value of data.
     *
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * Gets the value of was_processed.
     *
     * @return boolean
     */
    public function getWasProcessed()
    {
        return $this->was_processed;
    }

    /**
     * Gets the value of has_notified.
     *
     * @return boolean
     */
    public function getHasNotified()
    {
        return $this->has_notified;
    }
}

