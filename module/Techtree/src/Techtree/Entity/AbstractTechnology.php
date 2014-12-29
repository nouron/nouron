<?php
namespace Techtree\Entity;

use Core\Entity\EntityInterface;

abstract class AbstractTechnology implements EntityInterface
{
    private $id;
    private $purpose;
    private $name;
    private $decay;
    private $row;
    private $column;
    private $max_status_points;
    private $ap_for_levelup;
    private $required_building_id;
    private $required_building_level;

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
     * Gets the value of purpose.
     *
     * @return mixed
     */
    public function getPurpose()
    {
        return $this->purpose;
    }

    /**
     * Sets the value of purpose.
     *
     * @param mixed $purpose the purpose
     *
     * @return self
     */
    public function setPurpose($purpose)
    {
        $this->purpose = $purpose;

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
     * Gets the value of decay.
     *
     * @return mixed
     */
    public function getDecay()
    {
        return $this->decay;
    }

    /**
     * Sets the value of decay.
     *
     * @param mixed $decay the decay
     *
     * @return self
     */
    public function setDecay($decay)
    {
        $this->decay = $decay;
        return $this;
    }

    /**
     * Gets the value of row.
     *
     * @return mixed
     */
    public function getRow()
    {
        return $this->row;
    }

    /**
     * Sets the value of row.
     *
     * @param mixed $row the row
     * @return self
     */
    public function setRow($row)
    {
        $this->row = $row;
        return $this;
    }

    /**
     * Gets the value of column.
     *
     * @return mixed
     */
    public function getColumn()
    {
        return $this->column;
    }

    /**
     * Sets the value of column.
     *
     * @param mixed $column the column
     * @return self
     */
    public function setColumn($column)
    {
        $this->column = $column;
        return $this;
    }

    /**
     * Gets the value of max_status_points.
     *
     * @return mixed
     */
    public function getMaxStatusPoints()
    {
        return $this->max_status_points;
    }

    /**
     * Sets the value of max_status_points.
     *
     * @param mixed $max_status_points the max_status_points
     * @return self
     */
    public function setMaxStatusPoints($max_status_points)
    {
        $this->max_status_points = $max_status_points;
        return $this;
    }

    /**
     * Gets the value of ap_for_levelup.
     *
     * @return mixed
     */
    public function getApForLevelup()
    {
        return $this->ap_for_levelup;
    }

    /**
     * Sets the value of ap_for_levelup.
     *
     * @param mixed $ap_for_levelup the ap_for_levelup
     * @return self
     */
    public function setApForLevelup($ap_for_levelup)
    {
        $this->ap_for_levelup = $ap_for_levelup;
        return $this;
    }

    /**
     * Gets the value of required_building_id.
     *
     * @return mixed
     */
    public function getRequiredBuildingId()
    {
        return $this->required_building_id;
    }

    /**
     * Sets the value of required_building_id.
     *
     * @param mixed $required_building_id the required_building_id
     * @return self
     */
    public function setRequiredBuildingId($required_building_id)
    {
        $this->required_building_id = $required_building_id;
        return $this;
    }

    /**
     * Gets the value of required_building_level.
     *
     * @return mixed
     */
    public function getRequiredBuildingLevel()
    {
        return $this->required_building_level;
    }

    /**
     * Sets the value of required_building_level.
     *
     * @param mixed $required_building_level the required_building_level
     * @return self
     */
    public function setRequiredBuildingLevel($required_building_level)
    {
        $this->required_building_level = $required_building_level;
        return $this;
    }
}
