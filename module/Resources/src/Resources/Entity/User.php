<?php
namespace Resources\Entity;

use Nouron\Entity\AbstractEntity;
use Zend\Stdlib\Hydrator\ObjectProperty;

class User extends AbstractEntity
{
    public $user_id;
    public $credits;
    public $supply;


    /**
     * Sets the value of user_id.
     *
     * @param mixed $user_id the user_id
     *
     * @return self
     */
    public function setUserId($user_id)
    {
        $this->user_id = $user_id;

        return $this;
    }

    /**
     * Sets the value of credits.
     *
     * @param mixed $credits the credits
     *
     * @return self
     */
    public function setCredits($credits)
    {
        $this->credits = $credits;

        return $this;
    }

    /**
     * Sets the value of supply.
     *
     * @param mixed $supply the supply
     *
     * @return self
     */
    public function setSupply($supply)
    {
        $this->supply = $supply;

        return $this;
    }

    /**
     * Gets the value of user_id.
     *
     * @return mixed
     */
    public function getUserId()
    {
        return $this->user_id;
    }

    /**
     * Gets the value of credits.
     *
     * @return mixed
     */
    public function getCredits()
    {
        return $this->credits;
    }

    /**
     * Gets the value of supply.
     *
     * @return mixed
     */
    public function getSupply()
    {
        return $this->supply;
    }
}

