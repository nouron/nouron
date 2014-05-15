<?php
namespace Galaxy\Entity;

use Nouron\Entity\EntityInterface;
use Nouron\Entity\MapEntityInterface;

class SystemObject implements EntityInterface, MapEntityInterface
{
    public $id;
    public $name;
    public $x;
    public $y;
    public $type_id;
    public $sight;
    public $density;
    public $radiation;
    public $type;
    public $image_url;


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
     * Sets the value of type_id.
     *
     * @param mixed $type_id the type_id
     *
     * @return self
     */
    public function setTypeId($type_id)
    {
        $this->type_id = $type_id;

        return $this;
    }

    /**
     * Sets the value of sight.
     *
     * @param mixed $sight the sight
     *
     * @return self
     */
    public function setSight($sight)
    {
        $this->sight = $sight;

        return $this;
    }

    /**
     * Sets the value of density.
     *
     * @param mixed $density the density
     *
     * @return self
     */
    public function setDensity($density)
    {
        $this->density = $density;

        return $this;
    }

    /**
     * Sets the value of radiation.
     *
     * @param mixed $radiation the radiation
     *
     * @return self
     */
    public function setRadiation($radiation)
    {
        $this->radiation = $radiation;

        return $this;
    }

    /**
     * Sets the value of type.
     *
     * @param mixed $type the type
     *
     * @return self
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Sets the value of image_url.
     *
     * @param mixed $image_url the image_url
     *
     * @return self
     */
    public function setImageUrl($image_url)
    {
        $this->image_url = $image_url;

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
     * Gets the value of type_id.
     *
     * @return mixed
     */
    public function getTypeId()
    {
        return $this->type_id;
    }

    /**
     * Gets the value of sight.
     *
     * @return mixed
     */
    public function getSight()
    {
        return $this->sight;
    }

    /**
     * Gets the value of density.
     *
     * @return mixed
     */
    public function getDensity()
    {
        return $this->density;
    }

    /**
     * Gets the value of radiation.
     *
     * @return mixed
     */
    public function getRadiation()
    {
        return $this->radiation;
    }

    /**
     * Gets the value of type.
     *
     * @return mixed
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Gets the value of image_url.
     *
     * @return mixed
     */
    public function getImageUrl()
    {
        return $this->image_url;
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

