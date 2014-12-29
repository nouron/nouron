<?php
namespace Trade\Entity;

use Core\Entity\EntityInterface;

class Resource implements EntityInterface
{
    // attributes
    public $colony_id;
    public $direction;
    public $resource_id;
    public $amount;
    public $price;
    public $restriction;

    // additional attributes when using this entity with a view
    public $colony;
    public $username;
    public $user_id;
    public $race_id;
    public $faction_id;

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
        $this->colony_id = $colony_id;

        return $this;
    }

    /**
     * Gets the value of direction.
     *
     * @return mixed
     */
    public function getDirection()
    {
        return $this->direction;
    }

    /**
     * Sets the value of direction.
     *
     * @param mixed $direction the direction
     *
     * @return self
     */
    public function setDirection($direction)
    {
        $this->direction = $direction;

        return $this;
    }

    /**
     * Gets the value of resource_id.
     *
     * @return mixed
     */
    public function getResourceId()
    {
        return $this->resource_id;
    }

    /**
     * Sets the value of resource_id.
     *
     * @param mixed $resource_id the resource_id
     *
     * @return self
     */
    public function setResourceId($resource_id)
    {
        $this->resource_id = $resource_id;

        return $this;
    }

    /**
     * Gets the value of amount.
     *
     * @return mixed
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * Sets the value of amount.
     *
     * @param mixed $amount the amount
     *
     * @return self
     */
    public function setAmount($amount)
    {
        $this->amount = $amount;

        return $this;
    }

    /**
     * Gets the value of price.
     *
     * @return mixed
     */
    public function getPrice()
    {
        return $this->price;
    }

    /**
     * Sets the value of price.
     *
     * @param mixed $price the price
     *
     * @return self
     */
    public function setPrice($price)
    {
        $this->price = $price;

        return $this;
    }

    /**
     * Gets the value of restriction.
     *
     * @return mixed
     */
    public function getRestriction()
    {
        return $this->restriction;
    }

    /**
     * Sets the value of restriction.
     *
     * @param mixed $restriction the restriction
     *
     * @return self
     */
    public function setRestriction($restriction)
    {
        $this->restriction = $restriction;

        return $this;
    }

    /**
     * Gets the value of colony.
     *
     * @return mixed
     */
    public function getColony()
    {
        return $this->colony;
    }

    /**
     * Sets the value of colony.
     *
     * @param mixed $colony the colony
     *
     * @return self
     */
    public function setColony($colony)
    {
        $this->colony = $colony;

        return $this;
    }

    /**
     * Gets the value of username.
     *
     * @return mixed
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * Sets the value of username.
     *
     * @param mixed $username the username
     *
     * @return self
     */
    public function setUsername($username)
    {
        $this->username = $username;

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
     * Gets the value of race_id.
     *
     * @return mixed
     */
    public function getRaceId()
    {
        return $this->race_id;
    }

    /**
     * Sets the value of race_id.
     *
     * @param mixed $race_id the race_id
     *
     * @return self
     */
    public function setRaceId($race_id)
    {
        $this->race_id = $race_id;

        return $this;
    }

    /**
     * Gets the value of faction_id.
     *
     * @return mixed
     */
    public function getFactionId()
    {
        return $this->faction_id;
    }

    /**
     * Sets the value of faction_id.
     *
     * @param mixed $faction_id the faction_id
     *
     * @return self
     */
    public function setFactionId($faction_id)
    {
        $this->faction_id = $faction_id;

        return $this;
    }
}

