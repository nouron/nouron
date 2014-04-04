<?php
namespace Techtree\Entity;

class Building extends AbstractTechnology
{
    public $prime_colony_only;
    public $max_level;

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
     * Sets the value of prime_colony_only.
     *
     * @param mixed $prime_colony_only the prime_colony_only
     * @return self
     */
    public function setPrimeColonyOnly($prime_colony_only)
    {
        $this->prime_colony_only = $prime_colony_only;
        return $this;
    }

    /**
     * Gets the value of max_level.
     *
     * @return integer
     */
    public function getMaxLevel()
    {
        return $this->max_level;
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
        $this->max_level = (int) $max_level;
        return $this;
    }
}
