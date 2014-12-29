<?php

/**
 * @package   Nouron_Core
 * @category  Entity
 */

namespace Core\Entity;

interface MapEntityInterface
{
    /**
     * @return integer
     */
    public function getX();

    /**
     * @param integer
     * @return self
     */
    public function setX($x);

    /**
     * @return integer
     */
    public function getY();

    /**
     * @param integer
     * @return self
     */
    public function setY($y);

    /**
     * @return array
     */
    public function getCoords();

    /**
     * @param array $coords
     * @return self
     */
    public function setCoords(array $coords);
}
