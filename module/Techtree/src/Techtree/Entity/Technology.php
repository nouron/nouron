<?php
namespace Techtree\Entity;

use Nouron\Entity\AbstractEntity;

class Technology extends AbstractEntity
{
    public $id;
    public $type;
    public $purpose;
    public $name;
    public $prime_colony_only;
    public $decay;
    public $tradeable;
    public $moving_speed;
    public $row;
    public $column;
    public $max_level;
    public $max_status_points;
    public $ap_for_levelup;

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
     * Gets the value of type.
     *
     * @return mixed
     */
    public function getType()
    {
        return $this->type;
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
     * Gets the value of name.
     *
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Gets the value of prime_colony_only.
     *
     * @return mixed
     */
    public function getPrimeColonyOnly()
    {
        return $this->prime_colony_only;
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
     * Gets the value of tradeable.
     *
     * @return mixed
     */
    public function getTradeable()
    {
        return $this->tradeable;
    }

    /**
     * Gets the value of moving_speed.
     *
     * @return mixed
     */
    public function getMovingSpeed()
    {
        return $this->moving_speed;
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
     * Gets the value of column.
     *
     * @return mixed
     */
    public function getColumn()
    {
        return $this->column;
    }

    /**
     * Gets the value of max_level.
     *
     * @return mixed
     */
    public function getMaxLevel()
    {
        return $this->max_level;
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
     * Gets the value of ap_for_levelup.
     *
     * @return mixed
     */
    public function getApForLevelup()
    {
        return $this->ap_for_levelup;
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
     * Sets the value of prime_colony_only.
     *
     * @param mixed $prime_colony_only the prime_colony_only
     *
     * @return self
     */
    public function setPrimeColonyOnly($prime_colony_only)
    {
        $this->prime_colony_only = $prime_colony_only;

        return $this;
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
     * Sets the value of tradeable.
     *
     * @param mixed $tradeable the tradeable
     *
     * @return self
     */
    public function setTradeable($tradeable)
    {
        $this->tradeable = $tradeable;

        return $this;
    }

    /**
     * Sets the value of moving_speed.
     *
     * @param mixed $moving_speed the moving_speed
     *
     * @return self
     */
    public function setMovingSpeed($moving_speed)
    {
        $this->moving_speed = $moving_speed;

        return $this;
    }

    /**
     * Sets the value of row.
     *
     * @param mixed $row the row
     *
     * @return self
     */
    public function setRow($row)
    {
        $this->row = $row;

        return $this;
    }

    /**
     * Sets the value of column.
     *
     * @param mixed $column the column
     *
     * @return self
     */
    public function setColumn($column)
    {
        $this->column = $column;

        return $this;
    }

    /**
     * Sets the value of max_level.
     *
     * @param mixed $max_level the max_level
     *
     * @return self
     */
    public function setMaxLevel($max_level)
    {
        $this->max_level = $max_level;

        return $this;
    }

    /**
     * Sets the value of max_status_points.
     *
     * @param mixed $max_status_points the max_status_points
     *
     * @return self
     */
    public function setMaxStatusPoints($max_status_points)
    {
        $this->max_status_points = $max_status_points;

        return $this;
    }

    /**
     * Sets the value of ap_for_levelup.
     *
     * @param mixed $ap_for_levelup the ap_for_levelup
     *
     * @return self
     */
    public function setApForLevelup($ap_for_levelup)
    {
        $this->ap_for_levelup = $ap_for_levelup;

        return $this;
    }
}

