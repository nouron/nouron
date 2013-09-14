<?php
namespace Galaxy\Entity;

use Nouron\Entity\AbstractEntity;

class Fleet extends AbstractEntity
{
    public $id;
    public $fleet;
    public $user_id;
    public $x;
    public $y;
    public $spot;


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
    public function setFleet($name)
    {
        $this->fleet = $name;

        return $this;
    }

    /**
     * Sets the value of user_id.
     *
     * @param mixed $user_id the user_id
     *
     * @return self
     */
    public function setUserId($user_id)
    {
        $this->user_id = $user_id;

        return $this;
    }

    /**
     * Sets the value of x.
     *
     * @param mixed $x the x
     *
     * @return self
     */
    public function setX($x)
    {
        $this->x = $x;

        return $this;
    }

    /**
     * Sets the value of y.
     *
     * @param mixed $y the y
     *
     * @return self
     */
    public function setY($y)
    {
        $this->y = $y;

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
     * Gets the value of id.
     *
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
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
     * Gets the value of user_id.
     *
     * @return mixed
     */
    public function getUserId()
    {
        return $this->user_id;
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
     * Gets the value of y.
     *
     * @return mixed
     */
    public function getY()
    {
        return $this->y;
    }

    /**
     * Gets the value of spot.
     *
     * @return mixed
     */
    public function getSpot()
    {
        return $this->spot;
    }
}

