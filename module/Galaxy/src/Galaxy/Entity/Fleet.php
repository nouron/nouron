<?php
namespace Galaxy\Entity;

use Nouron\Entity\AbstractEntity;

class Fleet extends AbstractEntity
{
    public $id;
    public $name;
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
    public function setName($name)
    {
        $this->name = $name;

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
}

