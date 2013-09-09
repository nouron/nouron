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
    public function getPrime_colony_only()
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
    public function getMoving_speed()
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
    public function getMax_level()
    {
        return $this->max_level;
    }

    /**
     * Gets the value of max_status_points.
     *
     * @return mixed
     */
    public function getMax_status_points()
    {
        return $this->max_status_points;
    }

    /**
     * Gets the value of ap_for_levelup.
     *
     * @return mixed
     */
    public function getAp_for_levelup()
    {
        return $this->ap_for_levelup;
    }
}

