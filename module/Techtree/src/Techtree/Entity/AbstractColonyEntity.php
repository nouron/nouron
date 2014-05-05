<?php
namespace Techtree\Entity;

use Nouron\Entity\AbstractEntity;
use Nouron\Entity\EntityInterface;

abstract class AbstractColonyEntity implements EntityInterface
{
    private $colony_id;
    private $level;
    private $status_points;
    private $ap_spend;

    /**
     * Gets the value of colony_id.
     *
     * @return mixed
     */
    public function getColonyId()
    {
        return $this->colony_id;
    }

    /**
     * Sets the value of colony_id.
     *
     * @param mixed $colony_id the colony_id
     *
     * @return self
     */
    public function setColonyId($colony_id)
    {
        $this->colony_id = abs($colony_id);

        return $this;
    }

    /**
     * Gets the value of level.
     *
     * @return integer
     */
    public function getLevel()
    {
        return $this->level;
    }

    /**
     * Sets the value of level.
     *
     * @param mixed $level the level
     * @return self
     */
    public function setLevel($level)
    {
        $level = (int) $level;
        if ($level<0) {
            $level = 0;
        }
        $this->level = $level;
        return $this;
    }

    /**
     * Gets the value of status_points.
     *
     * @return integer
     */
    public function getStatusPoints()
    {
        return $this->status_points;
    }

    /**
     * Sets the value of status_points.
     *
     * @param mixed $status_points the status_points
     * @return self
     */
    public function setStatusPoints($status_points)
    {
        $status_points = (int) $status_points;
        if ($status_points<0) {
            $status_points = 0;
        }
        $this->status_points = $status_points;
        return $this;
    }

    /**
     * Gets the value of ap_spend.
     *
     * @return integer
     */
    public function getApSpend()
    {
        return $this->ap_spend;
    }

    /**
     * Sets the value of ap_spend.
     *
     * @param mixed $ap_spend the ap_spend
     *
     * @return self
     */
    public function setApSpend($ap_spend)
    {
        $ap_spend = (int) $ap_spend;
        if ($ap_spend<0) {
            $ap_spend = 0;
        }
        $this->ap_spend = $ap_spend;
        return $this;
    }
}

