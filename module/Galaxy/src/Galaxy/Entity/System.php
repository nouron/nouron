<?php
namespace Galaxy\Entity;

use Nouron\Entity\AbstractEntity;

class System extends AbstractEntity
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
    public function setType_id($type_id)
    {
        $this->type_id = $type_id;

        return $this;
    }

    /**
     * Sets the value of background_image_url.
     *
     * @param mixed $background_image_url the background_image_url
     *
     * @return self
     */
    public function setBackground_image_url($background_image_url)
    {
        $this->background_image_url = $background_image_url;

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
}

