<?php
namespace Galaxy\Entity;

use Nouron\Entity\EntityInterface;
use Nouron\Entity\MapEntityInterface;

class System implements EntityInterface, MapEntityInterface
{
    public $id;
    public $name;
    public $x;
    public $y;
    public $type_id;
    public $background_image_url;
    public $sight;
    public $density;
    public $radiation;
    public $class;
    public $size;
    public $icon_url;
    public $image_url;

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
     * Gets the value of y.
     *
     * @return mixed
     */
    public function getY()
    {
        return $this->y;
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
     * Gets the value of type_id.
     *
     * @return mixed
     */
    public function getTypeId()
    {
        return $this->type_id;
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
     * Gets the value of background_image_url.
     *
     * @return mixed
     */
    public function getBackgroundImageUrl()
    {
        return $this->background_image_url;
    }

    /**
     * Sets the value of background_image_url.
     *
     * @param mixed $background_image_url the background_image_url
     *
     * @return self
     */
    public function setBackgroundImageUrl($background_image_url)
    {
        $this->background_image_url = $background_image_url;
        return $this;
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
     * Gets the value of density.
     *
     * @return mixed
     */
    public function getDensity()
    {
        return $this->density;
    }

    /**
     * Sets the value of density.
     *
     * @param mixed $density the density
     * @return self
     */
    public function setDensity($density)
    {
        $this->density = $density;
        return $this;
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
     * Sets the value of radiation.
     *
     * @param mixed $radiation the radiation
     * @return self
     */
    public function setRadiation($radiation)
    {
        $this->radiation = $radiation;
        return $this;
    }

    /**
     * Gets the value of class.
     *
     * @return mixed
     */
    public function getClass()
    {
        return $this->class;
    }

    /**
     * Sets the value of class.
     *
     * @param mixed $class the class
     * @return self
     */
    public function setClass($class)
    {
        $this->class = $class;
        return $this;
    }

    /**
     * Gets the value of size.
     *
     * @return mixed
     */
    public function getSize()
    {
        return $this->size;
    }

    /**
     * Sets the value of size.
     *
     * @param mixed $size the size
     * @return self
     */
    public function setSize($size)
    {
        $this->size = $size;
        return $this;
    }

    /**
     * Gets the value of icon_url.
     *
     * @return mixed
     */
    public function getIconUrl()
    {
        return $this->icon_url;
    }

    /**
     * Sets the value of icon_url.
     *
     * @param mixed $icon_url the icon_url
     * @return self
     */
    public function setIconUrl($icon_url)
    {
        $this->icon_url = $icon_url;
        return $this;
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
     * Sets the value of image_url.
     *
     * @param mixed $image_url the image_url
     * @return self
     */
    public function setImageUrl($image_url)
    {
        $this->image_url = $image_url;
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
