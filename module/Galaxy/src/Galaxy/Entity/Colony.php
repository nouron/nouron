<?php
namespace Galaxy\Entity;

use Nouron\Entity\AbstractEntity;

class Colony extends AbstractEntity
{
    public $id;
    public $name;
    public $system_object_id;
    public $spot;
    public $user_id;
    public $since_tick;
    public $is_primary;


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
     * Sets the value of name.
     *
     * @param mixed $name the name
     *
     * @return self
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Sets the value of system_object_id.
     *
     * @param mixed $system_object_id the system_object_id
     *
     * @return self
     */
    public function setSystem_object_id($system_object_id)
    {
        $this->system_object_id = $system_object_id;

        return $this;
    }

    /**
     * Sets the value of spot.
     *
     * @param mixed $spot the spot
     *
     * @return self
     */
    public function setSpot($spot)
    {
        $this->spot = $spot;

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
     * Sets the value of since_tick.
     *
     * @param mixed $since_tick the since_tick
     *
     * @return self
     */
    public function setSince_tick($since_tick)
    {
        $this->since_tick = $since_tick;

        return $this;
    }

    /**
     * Sets the value of is_primary.
     *
     * @param mixed $is_primary the is_primary
     *
     * @return self
     */
    public function setIs_primary($is_primary)
    {
        $this->is_primary = $is_primary;

        return $this;
    }
}

