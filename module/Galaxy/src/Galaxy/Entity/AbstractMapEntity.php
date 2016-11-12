<?php
namespace Galaxy\Entity;

use Core\Entity\EntityInterface;
use Core\Entity\MapEntityInterface;

abstract class AbstractMapEntity implements EntityInterface, MapEntityInterface
{
    protected $id;
    protected $name;
    protected $x;
    protected $y;

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
        if (!is_numeric($id) || $id < 0) {
            throw new \Core\Entity\Exception('invalid value for id');
        }
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
     * @return self
     */
    public function setName($name)
    {
        $this->name = $name;
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
        if (!is_numeric($x) || $x < 0) {
            throw new \Core\Entity\Exception('invalid value for x');
        }
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
        if (!is_numeric($y) || $y < 0) {
            throw new \Core\Entity\Exception('invalid value for y');
        }
        $this->y =(int) $y;
        return $this;
    }

    /**
     * @return array
     */
    public function getCoords()
    {
        return array(
            0 => $this->getX(),
            1 => $this->getY(),
            2 => 0
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
    }
}
