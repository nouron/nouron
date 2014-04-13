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
    public $system_object_name;
    public $x;
    public $y;
    public $type_id;
    public $sight;
    public $density;
    public $radiation;


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
     * Gets the value of name.
     *
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
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
     * Gets the value of system_object_id.
     *
     * @return integer
     */
    public function getSystemObjectId()
    {
        return $this->system_object_id;
    }

    /**
     * Sets the value of system_object_id.
     *
     * @param integer $system_object_id the system_object_id
     * @return self
     */
    public function setSystemObjectId($system_object_id)
    {
        $this->system_object_id = $system_object_id;

        return $this;
    }

    /**
     * Gets the value of spot.
     *
     * @return integer
     */
    public function getSpot()
    {
        return $this->spot;
    }

    /**
     * Sets the value of spot.
     *
     * @param integer $spot the spot
     *
     * @return self
     */
    public function setSpot($spot)
    {
        $this->spot = (int) $spot;
        return $this;
    }

    /**
     * Gets the value of user_id.
     *
     * @return integer
     */
    public function getUserId()
    {
        return $this->user_id;
    }

    /**
     * Sets the value of user_id.
     *
     * @param integer $user_id the user_id
     *
     * @return self
     */
    public function setUserId($user_id)
    {
        $this->user_id = $user_id;

        return $this;
    }

    /**
     * Gets the value of since_tick.
     *
     * @return integer
     */
    public function getSinceTick()
    {
        return $this->since_tick;
    }

    /**
     * Sets the value of since_tick.
     *
     * @param integer $since_tick the since_tick
     * @return self
     */
    public function setSinceTick($since_tick)
    {
        $this->since_tick = (int) $since_tick;

        return $this;
    }

    /**
     * Gets the value of is_primary.
     *
     * @return boolean
     */
    public function getIsPrimary()
    {
        return $this->is_primary;
    }

    /**
     * Sets the value of is_primary.
     *
     * @param boolean $is_primary
     * @return self
     */
    public function setIsPrimary($is_primary)
    {
        $this->is_primary = (bool) $is_primary;

        return $this;
    }

    /**
     * Gets the value of system_object_name.
     *
     * @return string
     */
    public function getSystemObjectName()
    {
        return $this->system_object_name;
    }

    /**
     * Sets the value of system_object_name.
     *
     * @param string $system_object_name the system_object_name
     * @return self
     */
    public function setSystemObjectName($system_object_name)
    {
        $this->system_object_name = $system_object_name;

        return $this;
    }

    /**
     * Gets the value of x.
     *
     * @return mixed
     */
    public function getX()
    {
        return $this->x;
    }

    /**
     * Sets the value of x.
     *
     * @param integer $x
     * @return self
     */
    public function setX($x)
    {
        $this->x = (int) $x;
        return $this;
    }

    /**
     * Gets the value of y.
     *
     * @return integer
     */
    public function getY()
    {
        return $this->y;
    }

    /**
     * Sets the value of y.
     *
     * @param integer $y
     * @return self
     */
    public function setY($y)
    {
        $this->y = $y;

        return $this;
    }

    /**
     * Gets the value of type_id.
     *
     * @return integer
     */
    public function getTypeId()
    {
        return $this->type_id;
    }

    /**
     * Sets the value of type_id.
     *
     * @param integer $type_id
     * @return self
     */
    public function setTypeId($type_id)
    {
        $this->type_id = (int) $type_id;
        return $this;
    }

    /**
     * Gets the value of sight.
     *
     * @return integer
     */
    public function getSight()
    {
        return $this->sight;
    }

    /**
     * Sets the value of sight.
     *
     * @param integer $sight
     * @return self
     */
    public function setSight($sight)
    {
        $this->sight = (int) $sight;
        return $this;
    }

    /**
     * Gets the value of density.
     *
     * @return integer
     */
    public function getDensity()
    {
        return $this->density;
    }

    /**
     * Sets the value of density.
     *
     * @param integer
     * @return self
     */
    public function setDensity($density)
    {
        $this->density = (int) $density;
        return $this;
    }

    /**
     * Gets the value of radiation.
     *
     * @return integer
     */
    public function getRadiation()
    {
        return $this->radiation;
    }

    /**
     * Sets the value of radiation.
     *
     * @param integer $radiation
     * @return self
     */
    public function setRadiation($radiation)
    {
        $this->radiation = $radiation;
        return $this;
    }

    /**
     * @return array
     */
    public function getCoords()
    {
        return array(
            0 => $this->getX(),
            1 => $this->getY()
        );
    }
}
