<?php
namespace Techtree\Entity;

class ColonyShip extends AbstractColonyEntity
{
    private $ship_id;

    /**
     * Gets the value of ship_id.
     *
     * @return mixed
     */
    public function getShipId()
    {
        return $this->ship_id;
    }

    /**
     * Sets the value of ship_id.
     *
     * @param mixed $ship_id the ship_id
     * @return self
     */
    public function setShipId($ship_id)
    {
        $this->ship_id = abs($ship_id);
        return $this;
    }
}

