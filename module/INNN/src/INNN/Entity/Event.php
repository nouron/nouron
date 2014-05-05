<?php
namespace INNN\Entity;

use Nouron\Entity\EntityInterface;

class Event implements EntityInterface
{
    private $id;
    private $user_id;
    private $tick;
    private $event;
    private $area;
    private $parameters;

    /**
     * Sets the value of id.
     *
     * @param mixed $id the id
     *
     * @return self
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Sets the value of user_id.
     *
     * @param mixed $user_id the user_id
     *
     * @return self
     */
    public function setUser_id($user_id)
    {
        $this->user_id = $user_id;

        return $this;
    }

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
     * Sets the value of event.
     *
     * @param mixed $event the event
     *
     * @return self
     */
    public function setEvent($event)
    {
        $this->event = $event;

        return $this;
    }

    /**
     * Sets the value of area.
     *
     * @param mixed $area the area
     *
     * @return self
     */
    public function setArea($area)
    {
        $this->area = $area;

        return $this;
    }

    /**
     * Sets the value of parameters.
     *
     * @param mixed $parameters the parameters
     *
     * @return self
     */
    public function setParameters($parameters)
    {
        $this->parameters = $parameters;

        return $this;
    }

    /**
     * Gets the value of id.
     *
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Gets the value of user_id.
     *
     * @return mixed
     */
    public function getUser_id()
    {
        return $this->user_id;
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
     * Gets the value of event.
     *
     * @return mixed
     */
    public function getEvent()
    {
        return $this->event;
    }

    /**
     * Gets the value of area.
     *
     * @return mixed
     */
    public function getArea()
    {
        return $this->area;
    }

    /**
     * Gets the value of parameters.
     *
     * @return mixed
     */
    public function getParameters()
    {
        return $this->parameters;
    }
}

