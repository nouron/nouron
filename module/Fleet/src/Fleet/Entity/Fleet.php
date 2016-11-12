<?php
namespace Fleet\Entity;

use Galaxy\Entity\AbstractMapEntity;

class Fleet extends AbstractMapEntity
{
    private $user_id;
    private $spot;

    /**
     * Sets the value of user_id.
     *
     * @param integer $user_id
     * @return self
     */
    public function setUserId($user_id)
    {
        if (!is_numeric($user_id) || $user_id < 0) {
            throw new \Core\Entity\Exception('invalid user id');
        }
        $this->user_id = $user_id;
        return $this;
    }

    /**
     * Sets the value of spot.
     *
     * @param integer $spot
     * @return self
     */
    public function setSpot($spot)
    {
        if (!is_numeric($spot) || $spot <0 || $spot > 9) {
            throw new \Core\Entity\Exception('invalid value for spot');
        }
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

