<?php
namespace Fleet\Entity;

use Nouron\Entity\EntityInterface;
use Nouron\Entity\MapEntityInterface;

class Fleet implements EntityInterface, MapEntityInterface
{
    private $id;
    private $fleet;
    private $user_id;
    private $x;
    private $y;
    private $spot;

    /**
     * Sets the value of id.
     *
     * @param integer
     * @return self
     */
    public function setId($id)
    {
        if (!is_numeric($id) || $id < 0) {
            throw new \Nouron\Entity\Exception('invalid id');
        }
        $this->id = $id;
        return $this;
    }

    /**
     * Sets the value of name.
     *
     * @param string $name the name
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
     * @param integer
     * @return self
     */
    public function setUserId($user_id)
    {
        if (!is_numeric($user_id) || $user_id < 0) {
            throw new \Nouron\Entity\Exception('invalid user id');
        }
        $this->user_id = $user_id;
        return $this;
    }

    /**
     * Sets the value of x.
     *
     * @param integer
     * @return self
     */
    public function setX($x)
    {
        if (!is_numeric($x)) {
            throw new \Nouron\Entity\Exception('invalid value for x coordinate');
        }
        $this->x = (int) $x;
        return $this;
    }

    /**
     * Sets the value of y.
     *
     * @param integer
     * @return self
     */
    public function setY($y)
    {
        if (!is_numeric($y)) {
            throw new \Nouron\Entity\Exception('invalid value for y coordinate');
        }
        $this->y = (int) $y;
        return $this;
    }

    /**
     * Sets the value of spot.
     *
     * @param integer
     * @return self
     */
    public function setSpot($spot)
    {
        if (!is_numeric($spot) || $spot <0 || $spot > 9) {
            throw new \Nouron\Entity\Exception('invalid value for spot');
        }
        $this->spot = (int) $spot;
        return $this;
    }

    /**
     * Gets the value of id.
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
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

    public function getFleet()
    {
        return $this->fleet;
    }

    /**
     * Gets the value of x.
     *
     * @return integer
     */
    public function getX()
    {
        return $this->x;
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
     * Gets the value of spot.
     *
     * @return integer
     */
    public function getSpot()
    {
        return $this->spot;
    }

    /**
     * @return array
     */
    public function getCoords()
    {
        return array(
            0 => $this->getX(),
            1 => $this->getY(),
            2 => $this->getSpot()
        );
    }

    /**
     * @param arrray $coords
     * @return null
     */
    public function setCoords(array $coords)
    {
        $this->setX($coords[0]);
        $this->setY($coords[1]);
        $this->setSpot($coords[2]);
    }
}

